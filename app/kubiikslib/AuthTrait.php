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
use App\User;
use App\Account;

trait AuthTrait {
 use ThrottlesLogins;           //Add Throttle traits

 protected $AuthTrait_user;     //Contains the user to avoid multiple searches
 protected $AuthTrait_accounts; //Contains the accounts to avoid multiple searches
 //Sets current user in order to avoid multiple query
 public function setCurrentUser($id) {
  $this->AuthTrait_user = User::find($id);
  $this->AuthTrait_accounts = $this->AuthTrait_user->accounts()->get();
 }

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


 //Get the selected account
 public function getAccount($access = null) {
  return response()->json([
    'response' => 'multiple_access',
    'message' => 'test',
  ],200);  
  if ($access != null)  
    return $this->AuthTrait_accounts->where('access', $access)->first();
  else  
    return $this->AuthTrait_accounts->first(); 
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
        //Check if user already registerd in the Profiles
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required|unique:users'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'response' => 'error',
                'message' => 'user_already_registered'
            ],400);
        }       
        //Check for all parameters
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'firstName' => 'required|min:2',
            'lastName' => 'required|min:2',
            'mobile' => 'required|min:10|max:10',
            'password'=> 'required|min:4'
        ]);
        if ($validator->fails()) {
            return response()
                ->json([
                    'response' => 'error',
                    'message' => 'validation_failed',
                    'errors' => $validator->errors()
                ], 400);            
        }
        //FIRST: we create a Profile
        $user = User::create([
            'firstName' => $request->get('firstName'),           
            'lastName' => $request->get('lastName'),
            'mobile' => $request->get('mobile'),
            'email' => $request->get('email'),
            'avatar' => 'url(' . $request->get('avatar') . ')',
            'emailValidationKey' => Str::random(50)
        ]);
        //We don't assign any Role as user has only 'default' access when creating it

        //SECOND: we create the standard User (account)
        $account = new Account;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make($request->get('password'), ['rounds' => 12]);
        $user->accounts()->save($account);
  
        //THIRD: Send email with validation key
/*        $data = [
            'name' =>  $user->firstName,
            'key' => Config::get('constants.API_URL') . '/api/auth/emailvalidate?id=' . 
                    $user->id  .
                    '&key=' .
                    $user->emailValidationKey
        ];*/
        $key = Config::get('constants.API_URL') . '/api/auth/emailvalidate?id=' . 
                $user->id  .
                '&key=' .
                $user->emailValidationKey;

        $data = ['html' => "<div><h2>" . $user->firstName . ", bienvenu(e) au GMA500</h2>
        <p>Vous n'avez pas encore confirmé votre adresse électronique.</p>
        <p>Vous pouvez confirmer votre adresse électronique en cliquant sur le lien suivant</p>
        <a href=\"" . $key . "\">Confirmer mon adresse électronique</a>
        </div>"];

        Mail::send('emails.generic',$data, function($message) use ($user)
        {
            $message->from(Config::get('constants.EMAIL_FROM_ADDRESS'), Config::get('constants.EMAIL_FROM_NAME'));
            $message->replyTo(Config::get('constants.EMAIL_NOREPLY'));
            $message->to($user->email);
            $message->subject("GMA500: Confirmation de votre adresse électronique");
        });   
/*
        //Add user notification
        $notification = new Notification;
        $notification->text = "Bienvenu au site du GMA. Vous etes pré-inscrit";
        $notification->isRead = 0;
        $profile->notifications()->save($notification);
*/
        //FINALLY:: Return ok code
        return response()->json([
            'response' => 'success',
            'message' => 'signup_success',
        ], 200);
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
    return response()
      ->json([
          'response' => 'error',
          'message' => $validator->errors()->first(),
          'errors' => $validator->errors()
          ], 400);
  }
  //Get tokenLife depending on keepconnected
  $tokenLife = $this->getTokenLife($request->keepconnected);

  //Check for throttling count
  if ($this->hasTooManyLoginAttempts($request)) {
      $this->fireLockoutEvent($request);
      return response()->json(['response' =>'error','message' => 'too_many_logins'], 400);
  }   

  //Find if we have user with specified email
  $user = User::where('email', $request->email)->first();
  if ($user == null) {
    return response()->json([
      'response' => 'error',
      'message' => 'invalid_email_or_password'                                 
    ],400);     
  }

  $this->setCurrentUser($user->id);

 //Checks if there are multiple accounts for the user with current access
  if ($request->access == null && $this->AuthTrait_accounts->count()>1) {
    return response()->json([
      'response' => 'multiple_access',
      'message' => $this->AuthTrait_accounts->pluck('access')->toArray(),
    ],200);    
  }

  //Get current account
  if ($request->access !== null)
    $account = $this->AuthTrait_accounts->where('access', $request->access)->first();
  else 
    $account = $this->AuthTrait_accounts->first();

  //Make sure that there is one account we want to connect to
  if ($account == null) {
      return response()->json([
          'response' => 'error',
          'message' => 'invalid_email_or_password'                                 
      ],400); 
  }

  //Try to authenticate and get a token
  try {
    //We use key as is unique
    $credentials = ['key' => $account->key, 'password'=> $request->get('password')];
    $customClaims = ['user_id'=> $user->id, 'account_id'=>$account->id, 'exp' => Carbon::now()->addMinutes($tokenLife)->timestamp];
    if (!$token = JWTAuth::attempt($credentials,$customClaims)) {
        $this->incrementLoginAttempts($request); //Increments throttle count
        return response()->json([
            'response' => 'error',
            'message' => 'invalid_email_or_password'
        ],401);
    }    
  } catch (JWTAuthException $e) {
    return response()->json([
        'response' => 'error',
        'message' => 'failed_to_create_token',
    ],401);
  }

  //Check if isEmailValidated in the Profile if not invalidate token and return error
  JWTAuth::setToken($token) ;
  $account = JWTAuth::toUser();
  $user = User::find($account->user_id);
  if ($user->isEmailValidated == 0) {
      JWTAuth::invalidate($token);
      return response()->json([
          'response' => 'error',
          'message' => 'email_not_validated',
          ],401);            
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
          return response()->json(null,200);
      }

      JWTAuth::setToken($request->bearerToken()) ;
      //Get user id from the payload
      $payload = JWTAuth::parseToken()->getPayload();
      $user = User::find($payload->get('user_id'));
      $user->access = Account::find($payload->get('user_id'))->access;
      //Return all data
      /*$profile = Profile::with('roles')->with('groups')->with('notifications')->with('products')->find($user->profile_id);
      $notifsCount = Notification::where('profile_id', $user->profile_id)->where('isRead', 0)->count();
      $profile->access = $user->access;
      $profile->notifsUnreadCount = $notifsCount;*/
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
          return response()->json(null,200);
      }    
      JWTAuth::invalidate($request->bearerToken());
      return response()->json(null,200);
  }

}