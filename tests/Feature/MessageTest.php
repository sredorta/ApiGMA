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
use App\Notification;
use App\Message;

class MessageTest extends TestCase {

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


    public function testMessageValidAccountMemberOneTo() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 
        $this->login(['password'=>'Secure10','access' => Config::get('constants.ACCESS_MEMBER')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', []);
        //$response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'auth.member_required']);
    }

    public function testMessageValidAccountAdminOneTo() {
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

        $user = User::all()->last();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', []);
        //$response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'auth.member_required']);
    }


    public function testMessageInvalidGuardAccountDefault() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', []);
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'auth.member_required']);
    }

    public function testMessageInvalidGuardNotLoggedIn() {
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenInvalidException::class);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', []);
        dd($response->json());
        //$response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation.required']);
    }

}



