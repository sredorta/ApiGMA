<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\kubiikslib\Helper;
use Artisan;
use App\User;
use App\Account;
use App\Attachment;

class AuthUpdateTest extends TestCase {

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

    public function testUpdateInvalidFirstName() {
        $this->loginAs();
        $data = [
            'firstName' => 't'    
        ];
        $response = $this->post('api/auth/update', $data);
        //dd($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);
    }

    public function testUpdateInvalidLastName() {
        $this->loginAs();
        $data = [
            'firstName' => 't'    
        ];
        $response = $this->post('api/auth/update', $data);
        //dd($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);
    }

    public function testUpdateInvalidEmail() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $data = [
            'email' => 't'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        //dd($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.email']);
    }

    public function testUpdateInvalidEmailAlreadyRegistered() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=>'0623133222']);
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $data = [
            'email' => 'sergi.redorta2@hotmail.com'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.update_email']);
    }

    public function testUpdateInvalidMobile() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $data = [
            'mobile' => '06bb'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);
    }

    /*
    public function testUpdateInvalidMobileNumber() {
        $this->loginAs();
        $data = [
            'mobile' => '0423133212'    
        ];
        $response = $this->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }*/

    public function testUpdateInvalidMobileAlreadyRegistered() {
        $this->signup(['email'=>'sergi.redorta2@hotmail.com', 'mobile'=>'0623133222']);
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $data = [
            'mobile' => '0623133222'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.update_phone_found']);        
    }

    public function testUpdateInvalidPasswordMissingNewPassword() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $data = [
            'password_old' => 'Secure0'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);
    }

    public function testUpdateInvalidPasswordMissingOldPassword() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $data = [
            'password_new' => 'Secure0'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);
    }

    public function testUpdateInvalidPasswordTooShort() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $data = [
            'password_old' => 'AAA',
            'password_new' => 'BBB'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);
    }
    public function testUpdateInvalidPasswordWrong() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $data = [
            'password_old' => 'Secure22',
            'password_new' => 'Secure33'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.update_password']);
    }

    
    public function testUpdateInvalidPasswordMultipleAccessSwitchedPassword() {
        $this->loginAsMultiple();  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $data = [
            'password_old'=> 'Secure2',
            'password_new' => 'Secure22'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.update_password']);
    }    


    public function testUpdateAvatarValidNotDefault() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $testfile = "test.jpg";
        $path = dirname(__DIR__) . '/storage/test_files/' . $testfile;
        $mime = Storage::disk('public')->mimeType('/test_files/' . $testfile);
        $file = new UploadedFile($path, $testfile, filesize($path), $mime, null, true);       

        $data = [
            'avatar' => $file
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseMissing('attachments', [
            'file_name' => "userdefault.jpg",
        ]);
        $this->assertDatabaseHas('attachments', [
            'id' => 2,
        ]);
    }




    ////////////////////////////////////////////////////////////////////////
    // Valid testing
    ////////////////////////////////////////////////////////////////////////
    public function testUpdateValidFirstName() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $data = [
            'firstName' => 'Toto'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseHas('users', [
            'id' => 1,
            'firstName' => 'Toto'
        ]);
    }

    public function testUpdateValidLastName() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $data = [
            'lastName' => 'Toto'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseHas('users', [
            'id' => 1,
            'lastName' => 'Toto'
        ]);
    }

    public function testUpdateValidEmail() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->first();   //Store old key
        $oldkey = $user->emailValidationKey;

        $data = [
            'email' => 'test@email.com'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);

        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseHas('users', [
            'id' => 1,
            'email' => 'test@email.com',
            'isEmailValidated' => false
        ]);
        $this->assertDatabaseMissing('users', [
            'id' => 1,
            'email' => 'test@email.com',
            'emailValidationKey' => $oldkey
        ]);
    }

    public function testUpdateValidPhone() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $data = [
            'mobile' => '0699999999'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);

        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseHas('users', [
            'id' => 1,
            'mobile' => '0699999999'
        ]);
    }   

    public function testUpdateValidPassword() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();   //Store old key
        $oldpassword = $user->accounts()->first()->password;
        $data = [
            'password_old' => 'Secure0',
            'password_new' => 'Secure11'    
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseMissing('accounts', [
            'id' => 1,
            'password' => $oldpassword
        ]);
    }    

    public function testUpdateValidPasswordMultipleAccessDefault() {
        $this->loginAsMultiple();  //Secure0 : DEFAULT, Secure1 : MEMBER, Secure2 : ADMIN
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        //Get old password
        $account = $user->accounts()->where('access', Config::get('constants.ACCESS_DEFAULT'))->first();
        $passOld = $account->password;
        $data = [
            'password_old'=> 'Secure0',
            'password_new' => 'Secure22'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
            'access' => Config::get('constants.ACCESS_DEFAULT'),
            'password' => $passOld
        ]);
        $this->assertDatabaseMissing('accounts', [
            'password' => $passOld
        ]);        

    }   

    //Validate update language
    public function testUpdateInValidLanguageNotSupported() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $data = [
            'language'=> 'xy'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        //dd($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.language_unsupported']);
        $this->assertDatabaseMissing('users', [
            'id' => 1,
            'language' => 'xy'
        ]);
    }
    public function testUpdateInValidLanguageFormat() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $data = [
            'language'=> 'dummy'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        //dd($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'auth.language_unsupported']);
        $this->assertDatabaseMissing('users', [
            'id' => 1,
            'language' => 'xy'
        ]);
    }    

    public function testUpdateValidLanguageFr() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $data = [
            'language'=> 'fr'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        //dd($response->json());
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseHas('users', [
            'id' => 1,
            'language' => 'fr'
        ]);
    }   
    public function testUpdateValidLanguageEn() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $data = [
            'language'=> 'en'
        ];   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/auth/update', $data);
        //dd($response->json());
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'auth.update_success']);
        $this->assertDatabaseHas('users', [
            'id' => 1,
            'language' => 'en'
        ]);
    }       

    ////////////////////////////////////////////////////////////////////////
    // Guard testing
    ////////////////////////////////////////////////////////////////////////    
    public function testUpdateInvalidNotLoggedIn() {
        $data = [
            'firstName' => 'Sergi'    
        ];
        $response = $this->post('api/auth/update', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>__('auth.login_required')]);
    }

}