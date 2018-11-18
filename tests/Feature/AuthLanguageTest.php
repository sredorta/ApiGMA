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
            'language' => 'fr'
        ]);
    }

    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpEmpty() {
        $response = $this->withHeaders(['Accept-Language'=> null])->post('api/auth/resetpassword', ['titi'=> '']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'Le champ adresse email est obligatoire.']);
    }
    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpUnsupportedLanguage() {
        $response = $this->withHeaders(['Accept-Language'=> 'es-ES,es'])->post('api/auth/resetpassword', ['titi'=> '']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'Le champ adresse email est obligatoire.']);
    }
    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpBadFormat() {
        $response = $this->withHeaders(['Accept-Language'=> 'dummy'])->post('api/auth/resetpassword', ['titi'=> '']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'Le champ adresse email est obligatoire.']);
    }

    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpFrench() {
        $response = $this->withHeaders(['Accept-Language'=> 'fr-FR,fr'])->post('api/auth/resetpassword', ['titi'=> '']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'Le champ adresse email est obligatoire.']);
    }

    //Language given through http request and not logged in
    public function testAuthLanguageThroughHttpEnglish() {
        $response = $this->withHeaders(['Accept-Language' => 'en-US,en'])->post('api/auth/resetpassword', ['titi'=> '']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'The adresse email field is required.']);
    }

    //Language selection with user logged in
    public function testAuthLanguageThroughUserSettingFr() {
        $this->loginAs();
        $response = $this->withHeaders(['Accept-Language' => 'en-US,en'])->post('api/auth/update', ['titi'=> '']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'Le champ adresse email est obligatoire.']);
    }
    //Language selection with user logged in
    public function testAuthLanguageThroughUserSettingEn() {
        $this->loginAs();
        $user = User::all()->last();
        $user->language = 'en';
        $user->save();
        $response = $this->withHeaders(['Accept-Language' => 'fr-FR,fr'])->post('api/auth/update', ['titi'=> '']);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'The adresse email field is required.']);
    }

}