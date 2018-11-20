<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\kubiikslib\Helper;
use Artisan;
use App\User;
use App\Account;
use App\Notification;

class AuthAccountTest extends TestCase {

    //Database setup
    public function setUp() {
        parent::setUp();
        
        Mail::fake();        //Avoid sending emails
        Artisan::call('migrate');
    }

    //Clean up after the test
    public function tearDown() {
        parent::tearDown();
    }

    //Add account
    public function testAuthAccountAddInvalidAccessName() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'test_access'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/create', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.account_not_available']);
    }

    public function testAuthAccountAddInvalidUserId() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 10,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/create', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.user_not_found']);
    }

    public function testAuthAccountAddInvalidUserAlreadyHaveAccount() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure33', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account);  

        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/create', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.account_already']);
    }

    public function testAuthAccountAddValid() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/create', $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('accounts', [
            'user_id' => 1,
            'access' => 'Admin'
        ]);

        //Check user has notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => 1,
            'text' => 'notification.account_added'
        ]);
    }

    //Delete account
    public function testAuthAccountDeleteInvalidAccessName() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'test_access'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/auth/account/delete', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.account_not_available']);
    }

    public function testAuthAccountDeleteInvalidUserId() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 10,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/auth/account/delete', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.user_not_found']);
    }

    public function testAuthAccountDeleteInvalidUserNotHaveAccount() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/auth/account/delete', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.account_not_found']);
    }

    public function testAuthAccountDeleteValid() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure33', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account);         
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/auth/account/delete', $data);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('accounts', [
            'user_id' => 1,
            'access' => 'Admin'
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => 1,
            'text' => 'notification.account_deleted'
        ]);

    }

    //toggleAccount

    public function testAuthAccountToggleInvalidUserId() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 10
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/toggle', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.user_not_found']);
    }

    public function testAuthAccountToggleInvalidUserNoAccount() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $user = User::all()->last();
        $user->accounts()->delete();

        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/toggle', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.account_toggle']);
    }
    public function testAuthAccountToggleInvalidUserTwoAccounts() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure33', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account);           

        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/toggle', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.account_toggle']);
    }

    public function testAuthAccountToggleValidDefaultToMember() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/toggle', $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('accounts', [
            'user_id' => 1,
            'access' => Config::get('constants.ACCESS_MEMBER')
        ]);        
        $this->assertDatabaseMissing('accounts', [
            'user_id' => 1,
            'access' => Config::get('constants.ACCESS_DEFAULT')
        ]);        
        //Verify notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => 1,
            'text' => 'notification.account_added'
        ]);        

    }    

    public function testAuthAccountToggleValidMemberToDefault() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $user = User::all()->last();
        $user->accounts()->delete();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure33', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account);  

        $this->loginAsMultiple(['password'=> 'Secure2','access' => Config::get('constants.ACCESS_ADMIN')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/toggle', $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('accounts', [
            'user_id' => 1,
            'access' => Config::get('constants.ACCESS_DEFAULT')
        ]);        
        $this->assertDatabaseMissing('accounts', [
            'user_id' => 1,
            'access' => Config::get('constants.ACCESS_MEMBER')
        ]);        
        //Verify notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => 1,
            'text' => 'notification.account_deleted'
        ]);                
    }    






    //Guard checking
    public function testAuthAccountAddGuardDefault() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure0','access' => Config::get('constants.ACCESS_DEFAULT')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/create', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'auth.admin_required']);
    }    

    //Guard checking
    public function testAuthAccountAddGuardMember() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure1','access' => Config::get('constants.ACCESS_MEMBER')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/account/create', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'auth.admin_required']);
    }   

    public function testAuthAccountAddNotLogged() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->post('api/auth/account/create', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'auth.login_required']);
    }   

    public function testAuthAccountDeleteGuardDefault() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $this->loginAsMultiple(['password'=> 'Secure0','access' => Config::get('constants.ACCESS_DEFAULT')]);  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $auth = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/auth/account/delete', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'auth.admin_required']);
    }    

    public function testAuthAccountDeleteNotLogged() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=> '0623133222']);
        $user = User::all()->last();
        $data = [
            'user_id'=> 1,
            'access' => 'Admin'
        ];   
        $response = $this->delete('api/auth/account/delete', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'auth.login_required']);
    }  
/*

    public function testLogin() {



        $response = $this->post('api/auth/login', [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0',
            'keepconnected' => false
        ]);
        dd(User::where('email', 'sergi.redorta@hotmail.com')->get()->first());
        dd($response->json()); //This is our response

        $response->assertStatus(200);  //expected status
    }*/

/*
    public function testIncorrectData() {
        $response = $this->post('api/user/document/add', [
            'attachable_type' => 'toto',
            'attachable_id' => '3'
        ]);
        $response->assertStatus(400);  //expected status
    }*/

}