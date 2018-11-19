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
use App;
use Artisan;
use App\User;
use App\Account;

class AuthLanguageTest extends TestCase {

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


    public function testAuthDefaultLanguageStoredDatabase() {
        $this->signup(['email'=>"test@email.com", 'mobile'=>'0623133244']);

        $this->assertDatabaseHas('users', [
            'language' => 'en'
        ]);
    }

    public function testAuthLanguageStoredDatabaseFrHeaders() {
        $default = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'Sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133212',
            'password'=> 'Secure0'            
        ];
        $response = $this->withHeaders(['Accept-Language'=> 'fr-FR,fr'])->post('api/auth/signup', $default);
        $this->assertDatabaseHas('users', [
            'language' => 'fr'
        ]);
    }
    public function testAuthLanguageStoredDatabaseEnHeaders() {
        $default = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'Sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133212',
            'password'=> 'Secure0'            
        ];
        $response = $this->withHeaders(['Accept-Language'=> 'en-US,en'])->post('api/auth/signup', $default);
        $this->assertDatabaseHas('users', [
            'language' => 'en'
        ]);
    }

    public function testAuthLanguageStoredDatabaseItHeaders() {
        $default = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'Sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133212',
            'password'=> 'Secure0'            
        ];
        $response = $this->withHeaders(['Accept-Language'=> 'it-IT,it'])->post('api/auth/signup', $default);
        $this->assertDatabaseHas('users', [
            'language' => 'fr'
        ]);
    }

    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpEmpty() {
        $response = $this->withHeaders(['Accept-Language'=> null])->get('api/auth/lang/any');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);

        $response = $this->withHeaders(['Accept-Language'=> null])->get('api/auth/lang/unregistered');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);        
    }

    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpUnsupportedLanguage() {
        $response = $this->withHeaders(['Accept-Language'=> 'es-ES,es'])->get('api/auth/lang/any');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);

        $response = $this->withHeaders(['Accept-Language'=> null])->get('api/auth/lang/unregistered');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);
    }
    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpBadFormat() {
        $response = $this->withHeaders(['Accept-Language'=> 'dummy'])->get('api/auth/lang/any');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);

        $response = $this->withHeaders(['Accept-Language'=> null])->get('api/auth/lang/unregistered');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);
    }

    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpFrench() {
        $response = $this->withHeaders(['Accept-Language'=> 'fr-FR,fr'])->get('api/auth/lang/any');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);

        $response = $this->withHeaders(['Accept-Language'=> null])->get('api/auth/lang/unregistered');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);
    }

    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpEnglish() {
        $response = $this->withHeaders(['Accept-Language' => 'en-US,en'])->get('api/auth/lang/any');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'english']);

        $response = $this->withHeaders(['Accept-Language'=> null])->get('api/auth/lang/unregistered');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'english']);
    }

    //Language selection with user logged in
    public function testAuthLanguageThroughUserSettingDefault() {
        $this->loginAs();
        $response = $this->withHeaders(['Accept-Language' => 'fr-FR,fr'])->get('api/auth/lang/registered');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'english']);
    }

    public function testAuthLanguageThroughUserSettingDefaultAdmin() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 
        $result = $this->login(['password'=>'Secure10','access' => Config::get('constants.ACCESS_ADMIN')]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->withHeaders(['Accept-Language' => 'fr-FR,fr', 'Authorization' => 'Bearer ' . $this->token])->get('api/auth/lang/admin');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'english']);
    }


    //Language selection with user logged in
    public function testAuthLanguageThroughUserSettingEn() {
        $this->loginAs();
        $user = User::all()->last();
        $user->language = 'en';
        $user->save();
        $response = $this->withHeaders(['Accept-Language' => 'fr-FR,fr','Authorization' => 'Bearer ' . $this->token])->get('api/auth/lang/registered');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'english']);
    }

    public function testAuthLanguageThroughUserSettingEnAdmin() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 
        $this->login(['password'=>'Secure10','access' => Config::get('constants.ACCESS_ADMIN')]);
        $user = User::all()->last();
        $user->language = 'en';
        $user->save();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/lang/admin');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'english']);
    }

    public function testAuthLanguageThroughUserSettingFrAdmin() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 
        $this->login(['password'=>'Secure10','access' => Config::get('constants.ACCESS_ADMIN')]);

        $user = User::all()->last();
        $user->language = 'fr';
        $user->save();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/lang/admin');
        $response->assertStatus(200)->assertExactJson(['response' => 'success', 'message' => 'francais']);
    }


}