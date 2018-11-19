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

class AuthLogoutTest extends TestCase {

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

    ////////////////////////////////////////////////////////////////////////
    // Parameters testing
    ////////////////////////////////////////////////////////////////////////

    public function testLogoutValid() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/logout');
        $response->assertStatus(204);
    }
    
    public function testLogoutInValidNotLoggedIn() {
        $response = $this->post('api/auth/logout');
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => __('auth.login_required')]);
    }

    public function testLogoutInValidDummyToken() {
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenInvalidException::class);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . 'dummy_token'])->post('api/auth/logout');
        dd($response->json());
    }

    //Invalid token
    public function testLogoutInValidTokenInvalidated() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $this->invalidateToken();
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenBlacklistedException::class);
        $response = $this->post('api/auth/logout');
        dd($response->json()); //This is required to wait that exception is catched
    }   

    public function testAdminValidLogoutAdmin() {
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
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/logout');
        $response->assertStatus(204);
    }

}