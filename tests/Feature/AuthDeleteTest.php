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

class AuthDeleteValidateTest extends TestCase {

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

    //Clean database and files after delete
    public function testAuthDeleteValidCheckDataLeft() {
        $this->signup(['email'=>"test@email.com", 'mobile'=>'0623133244']);
        $this->loginAs();
        //Add dummy attachment
        $user = User::all()->last();
        $user->attachments()->create(['filepath'=>'test', 'function' => 'test','name'=> 'test', 'type'=> 'test', 'extension'=> 'test' ]);
        
        $response = $this->delete('api/auth/delete');
        
        $response->assertStatus(200)->assertExactJson([]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
        $this->assertDatabaseMissing('accounts', [
            'user_id' => $user->id
        ]);
        
        $this->assertDatabaseMissing('attachments', [
            'attachable_id' => $user->id,
            'attachable_type' => User::class
        ]);

    }


    //Guard check
    public function testAuthDeleteInvalidNotLogged() {
        $response = $this->delete('api/auth/delete');
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'not_loggedin']);
    }


/*
    public function testResetPasswordValidateEmailNotFound() {
        $this->signup();
        $response = $this->post('api/auth/resetpassword', ['email'=> 'toto@test.com']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'email_not_found']);
    }   

    public function testResetPasswordValidateValidSimpleAccess() {
        $this->signup();
        $user = User::all()->last();
        $oldpass = $user->accounts()->first()->password;
        $response = $this->post('api/auth/resetpassword', ['email'=> 'sergi.redorta@hotmail.com']);
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'password_reset_success']);
        $this->assertDatabaseMissing('accounts', [
            'email' => 'sergi.redorta@hotmail.com', 'password' => $oldpass
        ]);
    }    

    public function testResetPasswordValidateInvalidAccess() {
        $this->signup();
        $user = User::all()->last();
        $oldpass = $user->accounts()->first()->password;
        $response = $this->post('api/auth/resetpassword', ['email'=> 'sergi.redorta@hotmail.com', 'access'=>'dummy']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation_failed']);
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
        $response->assertStatus(200)->assertExactJson(['response' => 'success','message' => 'password_reset_success']);
        $this->assertDatabaseMissing('accounts', [
            'access' => Config::get('constants.ACCESS_ADMIN'),
            'email' => 'sergi.redorta@hotmail.com', 'password' => $oldpass
        ]);
    }        

    //Guard checkin
    public function testResetPasswordInvalidGuard() {
        $this->loginAs();
        $response = $this->post('api/auth/resetpassword', ['email'=>'sergi.redorta@hotmail.com']);
        $response->assertStatus(401)->assertExactJson(['response' => 'error','message' => 'already_loggedin']);
    }    
*/
}