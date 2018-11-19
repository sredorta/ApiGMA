<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\kubiikslib\Helper;
use Artisan;
use App\User;
use App\Account;

class AuthResetPasswordValidateTest extends TestCase {

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
        //Storage::deleteDirectory(base_path('tests/storage/images'));
    }

    public function testResetPasswordValidateInvalidParams() {
        $response = $this->post('api/auth/resetpassword', ['titi'=> '']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation.required']);
    }

    public function testResetPasswordValidateEmailNotFound() {
        $this->signup();
        $response = $this->post('api/auth/resetpassword', ['email'=> 'toto@test.com']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'auth.email_failed']);
    }   

    public function testResetPasswordValidateValidSimpleAccess() {
        $this->signup();
        $user = User::all()->last();
        $oldpass = $user->accounts()->first()->password;
        $response = $this->post('api/auth/resetpassword', ['email'=> 'sergi.redorta@hotmail.com']);
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'auth.reset_success']);
        $this->assertDatabaseMissing('accounts', [
            'email' => 'sergi.redorta@hotmail.com', 'password' => $oldpass
        ]);
    }    

    public function testResetPasswordValidateInvalidAccess() {
        $this->signup();
        $user = User::all()->last();
        $oldpass = $user->accounts()->first()->password;
        $response = $this->post('api/auth/resetpassword', ['email'=> 'sergi.redorta@hotmail.com', 'access'=>'dummy']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => __('auth.account_missing')]);
    }      

    public function testResetPasswordValidateValidMultipleAccessNotSpecified() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 

        $response = $this->post('api/auth/resetpassword', ['email'=> 'sergi.redorta@hotmail.com']);
        $response->assertStatus(200)->assertExactJson(['response' => 'multiple_access', 'message' => [Config::get('constants.ACCESS_DEFAULT'), Config::get('constants.ACCESS_ADMIN')]]);
    }    

    public function testResetPasswordValidateValidMultipleAccessSpecified() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $oldpass = Hash::make('Secure10', ['rounds' => 12]);
        $account->password = $oldpass;
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 

        $response = $this->post('api/auth/resetpassword', ['email'=> 'sergi.redorta@hotmail.com', 'access'=> Config::get('constants.ACCESS_ADMIN')]);
        $response->assertStatus(200)->assertExactJson(['response' => 'success','message' => 'auth.reset_success']);
        $this->assertDatabaseMissing('accounts', [
            'access' => Config::get('constants.ACCESS_ADMIN'),
            'email' => 'sergi.redorta@hotmail.com', 'password' => $oldpass
        ]);
    }        

    //Guard checkin
    public function testResetPasswordInvalidGuard() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/resetpassword', ['email'=>'sergi.redorta@hotmail.com']);
        $response->assertStatus(401)->assertExactJson(['response' => 'error','message' => __('auth.already_loggedin')]);
    }    

}
