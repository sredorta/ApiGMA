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
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation_failed']);
    }

    public function testResetPasswordValidateEmailNotFound() {
        $this->signup();
        $response = $this->post('api/auth/resetpassword', ['email'=> 'toto@test.com']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'email_not_found']);
    }   

    public function testResetPasswordValidateValidSimpleAccess() {
        $this->signup();
        $response = $this->post('api/auth/resetpassword', ['email'=> 'sergi.redorta@hotmail.com']);
        $response->assertStatus(200)->assertExactJson(['response' => 'error', 'message' => 'password_reset_success']);

    }      
    /*
              'response' => 'multiple_access',
              'message' => $access->toArray(),*/
}
