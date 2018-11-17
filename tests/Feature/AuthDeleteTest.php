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
        
        $response->assertStatus(204);

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

        //TODO: Verify anything that is being added roles,groups...
    }


    //Guard check
    public function testAuthDeleteInvalidNotLogged() {
        $response = $this->delete('api/auth/delete');
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'not_loggedin']);
    }
}