<?php
namespace App\kubiikslib; 

use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Carbon\Carbon;
use JWTAuth;
use Illuminate\Http\Request;
use Validator;
use App\kubiikslib\Helper;
use App\kubiikslib\EmailTrait;
use App\User;
use App\Account;
use App\Attachment;
use App\Notification;
use App;

trait AuthTrait {
 use ThrottlesLogins;           //Add Throttle traits
 use EmailTrait;                //Add email traits


  //Generates a random password
  public function generatePassword() {
    return Helper::generatePassword(10);
  }

  //Returns token life depending on keepconnected
  public function getTokenLife($keep = false) {
    if (!$keep || $keep == null) 
      return 120; //120 minuntes if we are not keeping connection
    return 43200;           //30 days if we keep connected
  }

  public function generateEmailKey() {
    return Helper::generateRandomStr(30);
  }


  //Function required by the throttler
  public function username() {
      return 'toto';
  }

  /////////////////////////////////////////////////////////////////////////////////////////
  //
  //  signup:
  //
  //  We create the new user and a new 'default' account and send email to validate email account
  //  we then add notification to user of welcome
  //  Finally we redirect to home
  //
  ////////////////////////////////////////////////////////////////////////////////////////
  public function signup(Request $request)
  {
        //Check first that we got all parameters required
        //Check for all parameters
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'firstName' => 'required|min:2',
            'lastName' => 'required|min:2',
            'mobile' => 'required|min:10|max:10',
            'password'=> 'required|min:4'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);          
        }        

        //Check if user already registerd in the Users
        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users',
            'mobile' => 'required|unique:users'
        ]);
        if ($validator->fails()) {
            return response()->json(['response' => 'error', 'message' => __('auth.user_already_exists')],400);
        }       

        $language = app::getLocale();

        //FIRST: we create a User
        $user = User::create([
            'firstName' => $request->get('firstName'),           
            'lastName' => $request->get('lastName'),
            'mobile' => $request->get('mobile'),
            'email' => $request->get('email'),
            'emailValidationKey' => $this->generateEmailKey(),
            'language' => $language
        ]);
        //We now create the Attachable with the image uploaded
        $attachment = new Attachment;
        if ($attachment->add($user->id, User::class, "avatar","images/users/". $user->id . "/", $request->get('avatar'))== null) {
            $user->delete();
            return response()
            ->json(['response' => 'error','message' => __('attachable.confirm_title')], 400);               
        };

        //SECOND: we create the standard User (account)
        $account = new Account;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make($request->get('password'), ['rounds' => 12]);
        $user->accounts()->save($account);
  
        //THIRD: Send email with validation key
        $key = Config::get('constants.API_URL') . '/api/auth/emailvalidate?id=' . 
                $user->id  .
                '&key=' .
                $user->emailValidationKey;
        $data = ['html' => "<div><h2>" . __('email.confirm_title', ['name'=>$user->firstName]) . "</h2>
        <p>" . __('email.confirm_line1') . "</p>
        <p>" . __('email.confirm_line2') . "</p>
        <a href=\"" . $key . "\">" . __('email.confirm_link') . "</a>
        </div>"];
        $this->sendEmail($user->email, __('email.confirm_subject'), $data);

        //Add user notification
        $notification = new Notification;
        $notification->text = __('notification.welcome', ['name'=>$user->firstName]);
        $user->notifications()->save($notification);

        //FINALLY:: Return ok code
        return response()->json([
            'response' => 'success',
            'message' => __('auth.signup_success'),
        ], 200);
  }

  /////////////////////////////////////////////////////////////////////////////////////////
  // emailValidate:
  //      id : Id of the user
  //      key: Email validation key
  //
  //  We get id and email and we check if it matches the one in the db, if it's the case
  //  we then set isEmailValidated to true
  //
  ////////////////////////////////////////////////////////////////////////////////////////
    public function emailValidate(Request $request) {
      $validator = Validator::make($request->all(), [
          'id' => 'required|numeric',
          'key' => 'required|string|min:10'
      ]);        
      if ($validator->fails()) {
          return view('emailvalidation')->with('result',0);
      }        
      //Check that we have user with the requested id
      $user = User::where('id', '=', $request->get('id'))->where('emailValidationKey','=',$request->get('key'));
      if (!$user->count()) {
          return view('emailvalidation')->with('result',0);
      }
      //We are correct here so we update 
      $user = $user->first();
      $user->isEmailValidated = 1;
      $user->save();
      
      return view('emailvalidation')->with('result',1)->with('url',Config::get('constants.SITE_URL'));
  }

  /////////////////////////////////////////////////////////////////////////////////////////
  //
  //  login:
  //      email
  //      password
  //      keepconnected
  //      access?
  //  We use throttle to avoid to many attempts
  //  If login is accepted we return the token
  //  If isEmailValidated is false we return error and invalidate token
  //
  ////////////////////////////////////////////////////////////////////////////////////////
 public function login(Request $request) {
  $token = null;

  //Check the input parameters
  $validator = Validator::make($request->all(), [
      'email' => 'required|email',
      'password' => 'required|min:4',
      'keepconnected' => 'nullable|boolean',
      'access' => 'nullable|min:3'
  ]);
  if ($validator->fails()) {
    return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
  }
  //Get tokenLife depending on keepconnected
  $tokenLife = $this->getTokenLife($request->keepconnected);

  //Check for throttling count
  if ($this->hasTooManyLoginAttempts($request)) {
      $this->fireLockoutEvent($request);
      return response()->json(['response' =>'error','message' => __('auth.throttle', ['seconds'=> "60"])], 400);
  }   

  //Find if we have user with specified email
  $user = User::where('email', $request->email)->first();
  if ($user == null) {
    return response()->json(['response' => 'error','message' => __('auth.failed')],400);     
  }
  $accounts = $user->accounts()->get();

 //Checks if there are multiple accounts for the user with current access
  if ($request->access == null && $accounts->count()>1) {
    return response()->json([
      'response' => 'multiple_access',
      'message' => $accounts->pluck('access')->toArray(),
    ],200);    
  }

  //Get current account
  if ($request->access !== null)
    $account = $accounts->where('access', $request->access)->first();
  else 
    $account = $accounts->first();

  //Make sure that there is one account we want to connect to
  if ($account == null) {
      return response()->json(['response' => 'error','message' => __('auth.account_missing')],400); 
  }

  //Check that password matches with the specified access
  if (!Hash::check($request->get('password'), $account->password)) {
    $this->incrementLoginAttempts($request); //Increments throttle count
    return response()->json(['response' => 'error','message' => __('auth.failed')],400);
  }

  //Try to authenticate and get a token
  try {
    //We use key as is unique
    $credentials = ['key' => $account->key, 'password'=> $request->get('password')];
    $customClaims = ['user_id'=> $user->id, 'account_id'=>$account->id, 'exp' => Carbon::now()->addMinutes($tokenLife)->timestamp];
    if (!$token = JWTAuth::attempt($credentials,$customClaims)) {
        $this->incrementLoginAttempts($request); //Increments throttle count
        return response()->json(['response' => 'error','message' => __('auth.failed')],401);
    }    
  } catch (JWTAuthException $e) {
    return response()->json([
        'response' => 'error',
        'message' => __('auth.failed'),
    ],401);
  }


  //Check if isEmailValidated in the User if not invalidate token and return error
  JWTAuth::setToken($token) ;
  $account = JWTAuth::toUser();

  $user = User::find($account->user_id);
  if ($user->isEmailValidated == 0) {
      JWTAuth::invalidate($token);
      return response()->json(['response' => 'error','message' => __('auth.failed')],401);            
  }

  //Return the token
  $object = (object) ['token' => $token];
  return response()->json($object,200);
 }

  /////////////////////////////////////////////////////////////////////////////////////////
  //
  //  getAuthUser:
  //
  //  We parse from the header the token recieved
  //  If is valid then we return the user as json else empty
  //
  ////////////////////////////////////////////////////////////////////////////////////////
  public function getAuthUser(Request $request){

      if ($request->bearerToken() === null) {
          return response()->json(null,204);
      }

      JWTAuth::setToken($request->bearerToken());

      //Get user id from the payload
      $payload = JWTAuth::setRequest($request)->parseToken()->getPayload();
      
      $user = User::find($payload->get('user_id'));
      $user->account = Account::find($payload->get('account_id'))->access;
      $avatar = $user->attachments->where('function','avatar')->first();
      if ($avatar == null) {
        $user->avatar = null;
      } else {
        $user->avatar = $avatar->filepath . $avatar->name;
      }
      //Return all data
      /*$profile = Profile::with('roles')->with('groups')->with('notifications')->with('products')->find($user->profile_id);
      $notifsCount = Notification::where('profile_id', $user->profile_id)->where('isRead', 0)->count();
      $profile->access = $user->access;
      $profile->notifsUnreadCount = $notifsCount;*/
      /*return response()
      ->json([
          'response' => 'error',
          'message' => $payload->get('user_id')
      ], 400);   */
      return response()->json($user,200);    
  } 

  /////////////////////////////////////////////////////////////////////////////////////////
  //
  //  logout:
  //
  //  Invalidates the token
  //
  ////////////////////////////////////////////////////////////////////////////////////////
  public function logout(Request $request){
      if ($request->bearerToken()=== null) {
          return response()->json([],204);
      }    
      JWTAuth::invalidate($request->bearerToken());
      return response()->json([],204);
  }

  /////////////////////////////////////////////////////////////////////////////////////////
  //  resetPassword:
  //      email : email of the user
  //
  //  We get id of the user from email change password and send email with new password
  //
  ////////////////////////////////////////////////////////////////////////////////////////
    public function resetPassword(Request $request) {
      $validator = Validator::make($request->all(), [
          'email' => 'required|email',
          'access' => 'nullable|min:3'
      ]);        
      //Check parameters
      if ($validator->fails()) {
          return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
      }
      $user = User::where('email', $request->email)->get();
      //Check that we have user with the requested email/access
      if ($user->count() == 0) {
        return response()
            ->json(['response' => 'error','message' => __('auth.email_failed')], 400);          
      }
      $user = $user->first();
      $accounts = $user->accounts()->get();
      //If access is not provided and we have multiple accounts return the list of available accounts
      if ($request->access == null && $accounts->count()>1) {
          $access = $accounts->pluck('access');
          return response()->json([
              'response' => 'multiple_access',
              'message' => $access->toArray(),
          ],200);
      }
      //Get the account
      if ($request->access !== null) {
          $account = $accounts->where('access', $request->access)->first();
      } else {
          $account = $accounts->first();
      }
      if ($account == null) {
        return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
      }

      //Regenerate a new password
      $newPass = $this->generatePassword();
      $account->password = Hash::make($newPass, ['rounds' => 12]);
      $account->save();

      //Send email with new password
      $data = ['html' => "<div><h2>" . __('auth.reset_title') . "</h2>
      <p>". __('auth.reset_text1') . "<span style=\"font-weight:bold\">". $newPass . "</span></p>
      <p>" . __('auth.reset_text2') .  $account->access . "</p>
      </div>"];
      $this->sendEmail($user->email, __('auth.reset_subject'), $data);

      return response()->json(['response' => 'success','message' => __('auth.reset_success')], 200); 
  }

    /////////////////////////////////////////////////////////////////////////////////////////
    //  update:
    //      firstName or lastName or email or mobile, or avatar or (password_new and password_old)
    //
    //  We need to be registered and we update the requested field
    //
    ////////////////////////////////////////////////////////////////////////////////////////
    public function update(Request $request) {
        $user = User::find($request->get('myUser'));
        //Update firstName if is required
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|min:2'
        ]);        
        if (!$validator->fails()) {
            $user->firstName = $request->firstName;
            $user->save();
            return response()->json(['response' => 'success','message' => __('auth.update_success')], 200);
        }
        //Update lastName if is required
        $validator = Validator::make($request->all(), [
            'lastName' => 'required|string|min:2'
        ]);        
        if (!$validator->fails()) {
            $user->lastName = $request->lastName;
            $user->save();
            return response()->json(['response' => 'success','message' => __('auth.update_success')], 200);
        }        
        //Update avatar if is required
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|string'
        ]);        
        if (!$validator->fails()) {
            //Delete the current avatar attachable
            $attachment = $user->attachments->where('function','avatar')->first()->delete(); //Remove old avatar
            $attachment = new Attachment;
            $avatar = $attachment->add($user->id, User::class, "avatar","images/users/". $user->id . "/", $request->get('avatar')); //Add new one
            $avatar = $avatar->filepath . $avatar->name;
            return response()->json([
                'response' => 'success',
                'message' => __('auth.update_success'),
                'avatar' => $avatar
            ], 200);
        } 

        //Update mobile if is required
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|numeric|unique:users'
        ]);       
        if ($validator->fails()) {
            $validatorU = Validator::make($request->all(), [
                'mobile' => 'unique:users'
            ]);
            if ($validatorU->fails()) {
                return response()->json(['response' => 'error','message' => __('auth.update_phone_found')],400);
            }
        }
        if (!$validator->fails()) {
            $user->mobile = $request->mobile;
            $user->save();
            return response()->json(['response' => 'success','message' => __('auth.update_success')], 200);
        }  

        //Update password
        $validator = Validator::make($request->all(), [
            'password_old' => 'required|string|min:5',
            'password_new' => 'required|string|min:5'
        ]);        
        if (!$validator->fails()) {
            //Check that password old matches
            $account = Account::find($request->get('myAccount'))->first();
            if (!Hash::check($request->get('password_old'), $account->password)) {
                return response()->json(['response' => 'error','message' => __('auth.update_password')],400);
            }
            $account->password = Hash::make($request->get('password_new'), ['rounds' => 12]);
            $account->save();
            return response()->json(['response' => 'success','message' => __('auth.update_success')], 200);
        }  

        //Update email if is required and then we need to set email validated to false and logout and send email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users'
        ]);        
        if ($validator->fails()) {
            $validatorU = Validator::make($request->all(), [
                'email' => 'unique:users'
            ]);
            if ($validatorU->fails()) {
                return response()->json(['response' => 'error','message' => __('auth.update_email')],400);
            }
        }
        if (!$validator->fails()) {
            $user->isEmailValidated = 0;
            $user->emailValidationKey = $this->generateEmailKey();
            $user->email = $request->email;
            $user->save();

            //Send email with validation key
            $key = Config::get('constants.API_URL') . '/api/auth/emailvalidate?id=' . 
                    $user->id  .
                    '&key=' .
                    $user->emailValidationKey;
            $data = ['html' => "<div><h2>" . __('email.change', ['name'=>$user->firstName]) . "</h2>
            <p>" . __('email.confirm_line1') . "</p>
            <p>" . __('email.confirm_line2') . "</p>
            <a href=\"" . $key . "\">" . __('email.confirm_link') . "</a>
            </div>"];
            $this->sendEmail($user->email, __('email.confirm_subject'), $data);
    

            //Invalidate the token
            JWTAuth::invalidate($request->bearerToken());
            return response()->json(['response' => 'success','message' => __('auth.update_success')], 200);
            
        }
        //If we got here, we have bad arguments
        return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);

    }

    /////////////////////////////////////////////////////////////////////////////////////////
    //
    //  delete:
    //
    //  Invalidates the token and deletes all data of a user and the associated data
    //  You need to be registered to be able to delete it and some cases will prevent deletion
    //
    ////////////////////////////////////////////////////////////////////////////////////////
    //Delete profile and all associated data
    public function delete(Request $request) { 
        //Check that user doesn't have any product assigned
        /*if (User::find($request->get('myUser'))->products()->count()>0) {
            return response()->json(["response" => "error", "message"=>"owning_products"],400);
        }*/
        //Invalidate the token
        JWTAuth::invalidate($request->bearerToken());
        User::find($request->get('myUser'))->delete();
        //Invalidate the token
        return response()->json([],204); 
    }    


}