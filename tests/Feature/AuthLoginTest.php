<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
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
        Artisan::call('migrate');
        //$this->loginAs();   //Create user and login and get current user in $this->user
    }

    //Clean up after the test
    public function tearDown() {
        parent::tearDown();

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
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }

    public function testLoginInvalidPassword() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 't'
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }

    public function testLoginInvalidUserNotFound() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0'
        ];
        $response = $this->post('api/auth/login', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'invalid_email_or_password']);
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
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'invalid_email_or_password']);
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
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'email_not_validated']);
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
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'invalid_email_or_password']);
    }    


    //Test with multiple accounts
    public function testLoginMultipleAccountsValid() {
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
        $account = new Account;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure0', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account);  

        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0'
        ];
        $response = $this->post('api/auth/login', $data);
        //dd($response->json());
        $response->assertStatus(200)->assertJson(['response'=>'multiple_access', 'message'=> [Config::get('constants.ACCESS_DEFAULT'), Config::get('constants.ACCESS_ADMIN')]]);
    }   

    //Test with multiple accounts specifying access
    public function testLoginMultipleAccountsValidSpecifyAccess() {
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
        $account = new Account;
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
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'already_loggedin']); 
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
        $response->assertStatus(200)->assertJsonCount(0); //Empty json response
    }    

    public function testLoginAuthUserInvalidToken() {
        $this->loginAs();
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenInvalidException::class);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . 'dummy_token'])->get('api/auth/user');
        dd($response->json()); //This is required to wait that exception is catched
    }

    public function testLoginAuthUserValidMultipleAccounts() {
        $this->signup();
        $account = new Account;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user = User::all()->last();
        $user->accounts()->save($account); 
        $this->login(['password'=>'Secure10','access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response->assertStatus(200)->assertJson(['id'=>1, 'email'=>'sergi.redorta@hotmail.com', 'account' => Config::get('constants.ACCESS_ADMIN')]);
    }

    public function testLoginAuthUserInValidMultipleAccountsInversedPasswords() {
        $this->signup();
        $account = new Account;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user = User::all()->last();
        $user->accounts()->save($account); 
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0',    //Use DEFAULT PASSWORD
            'access' => Config::get('constants.ACCESS_ADMIN')
        ];
        $response = $this->post('api/auth/login', $data);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response->assertStatus(401)->assertJson(['id'=>1, 'email'=>'sergi.redorta@hotmail.com', 'account' => Config::get('constants.ACCESS_ADMIN')]);
    }

    public function testLoginAuthUserValid() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response->assertStatus(200)->assertJson(['id'=>1, 'email'=>'sergi.redorta@hotmail.com', 'account' => Config::get('constants.ACCESS_DEFAULT')]);
        //TODO DEFINE EXACTLY WHICH DATA IS RETURNED AND PROCESS IT
        //dd($response->json());
    }   

}