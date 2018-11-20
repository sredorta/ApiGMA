<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\kubiikslib\Helper;
use Artisan;
use App\User;
use App\Account;

class AuthLoginTest extends TestCase {

    //Database setup
    public function setUp() {
        parent::setUp();
        
        Mail::fake();        //Avoid sending emails
        //Storage::fake('public');     //Avoid writting to storage
        Artisan::call('migrate');
        //$this->loginAs();   //Create user and login and get current user in $this->user
    }

    //Clean up after the test
    public function tearDown() {
        parent::tearDown();
        //echo base_path();
        //Storage::deleteDirectory(base_path('tests/storage/images'));
    }

    ////////////////////////////////////////////////////////////////////////
    // Parameters testing
    ////////////////////////////////////////////////////////////////////////
    public function testLoginInvalidEmailAddress() {
        $data = [
            'email' => 'toto',
            'password' => 'Secure0'        
        ];
        $response = $this->post('api/auth/login', $data);
        //dd($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.email']);
    }

    public function testLoginInvalidPassword() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 't'
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.min.string']);
    }

    public function testLoginInvalidUserNotFound() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0'
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.failed']);
    }

    public function testLoginInvalidAccess() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);        
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0',
            'access' => 'Admin'
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.account_missing']);
    }

    public function testLoginValidCheckIsValidatedEmail() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);        
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0'
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'auth.login_validate']);
    }


    public function testLoginValidLogin() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);     
        $user = User::all()->last();
        $user->isEmailValidated = true;
        $user->save();   
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0'
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(200)->assertJsonStructure(['token']);
    }    

    //Test invalid access
    public function testLoginInvalidUnauthorizedAccess() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);     
        $user = User::all()->last();
        $user->isEmailValidated = true;
        $user->save();   
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0',
            'access' => Config::get('constants.ACCESS_ADMIN')
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.account_missing']);
    }    

    public function testLoginInValidMultipleAccountsInversedPasswords() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure22', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0',    //Use DEFAULT PASSWORD
            'access' => Config::get('constants.ACCESS_ADMIN')
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.failed']);
    }

    public function testLoginInValidMultipleAccountsInversedPasswordsViceversa() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure22', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure22',    //Use DEFAULT PASSWORD
            'access' => Config::get('constants.ACCESS_DEFAULT')
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.failed']);
    }

    //Test with multiple accounts
    public function testLoginMultipleAccountsValid() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure33', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account);  

        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure33'
        ];
        $response = $this->post('api/auth/login', $data);
        //dd($response->json());
        $response->assertStatus(200)->assertJson(['access'=> [Config::get('constants.ACCESS_DEFAULT'), Config::get('constants.ACCESS_ADMIN')]]);
    }   

    //Test with multiple accounts specifying access
    public function testLoginMultipleAccountsValidSpecifyAccess() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account);  

        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure10',
            'access' => Config::get('constants.ACCESS_ADMIN')
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(200)->assertJsonStructure(['token']);
    }   

    public function testLoginInValidLoginThrottle() {
        $this->signup(['email'=> 'sergi.redortaThrottle@hotmail.com']); 
        $data = [
            'email' => 'sergi.redortaThrottle@hotmail.com',
            'password' => 'Secure2'
        ];
        for ($x=0; $x<10 ;$x++) {
            $this->post('api/auth/login', $data);
        }
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0'
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.throttle'] );
    }    


    ////////////////////////////////////////////////////////////////////////
    // GUARDS TEST
    ////////////////////////////////////////////////////////////////////////
    public function testLoginAlreadyLoggedIn() {
        $this->loginAs();

        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>__('auth.already_loggedin')]); 
    }    

    ////////////////////////////////////////////////////////////////////////
    // getAuthUser tests
    ////////////////////////////////////////////////////////////////////////
    public function testLoginAuthUserNotRegistered() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0'            
        ];        
        $response = $this->get('api/auth/user');
        $response->assertStatus(204); //Empty json response
    }    

    public function testLoginAuthUserInvalidToken() {
        $this->loginAs();
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenInvalidException::class);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . 'dummy_token'])->get('api/auth/user');
        dd($response->json()); //This is required to wait that exception is catched
    }

    public function testLoginAuthUserValidMultipleAccounts() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 
        $this->login(['password'=>'Secure10','access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response->assertStatus(200)->assertJson(['id'=>1, 'email'=>'sergi.redorta@hotmail.com', 'account' => Config::get('constants.ACCESS_ADMIN')]);
    }


    public function testLoginAuthUserValid() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response->assertStatus(200)->assertJson(['id'=>1, 'email'=>'sergi.redorta@hotmail.com', 'account' => Config::get('constants.ACCESS_DEFAULT')]);
        //TODO DEFINE EXACTLY WHICH DATA IS RETURNED AND PROCESS IT
        //dd($response->json());
    }   

    

}