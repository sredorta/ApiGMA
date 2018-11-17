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

class AuthEmailValidateTest extends TestCase {

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

    //TODO: This is somehow not working because we are returning views instead of jsons... need some debug here !
/*
    public function testEmailValidateValid() {
        $this->signup();
        $user = User::all()->last();
        $user->isEmailValidated = false;
        $user->save();

        $response = $this->get('api/auth/emailvalidate', ['id'=> $user->id, 'key'=> $user->emailValidationKey]);
        $this->assertDatabaseHas('users', [
            'email' => 'sergi.redorta@hotmail.com', 'isEmailValidated' => true
        ]);
    }

    public function testEmailValidateInValid() {
        $this->signup();
        $user = User::all()->last();
        $user->isEmailValidated = false;
        $user->save();

        $response = $this->get('api/auth/emailvalidate', ['id'=> $user->id, 'key'=> 'ThisIsADummyKey']);
        $this->assertDatabaseHas('users', [
            'email' => 'sergi.redorta@hotmail.com', 'isEmailValidated' => false
        ]);
    }    
    */
}

