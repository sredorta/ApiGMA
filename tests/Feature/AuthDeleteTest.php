<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Illuminate\Http\UploadedFile;
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
        Artisan::call('migrate');
    }

    //Clean up after the test
    public function tearDown() {
        parent::tearDown();
    }

    //Clean database and files after delete
    public function testAuthDeleteValidCheckDataLeft() {
        $this->signup(['email'=>"test@email.com", 'mobile'=>'0623133244']);
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        //Add document attachment
        $testfile = "test.pdf";
        $path = dirname(__DIR__) . '/storage/test_files/' . $testfile;
        $mime = Storage::disk('public')->mimeType('/test_files/' . $testfile);
        $file = new UploadedFile($path, $testfile, filesize($path), $mime, null, true);       
        $default = [
            'attachable_id' => $user->id,                            
            'attachable_type' => User::class,              
            'file' => $file,                        //Uploaded file
            //'default' => 'avatar',                  //Default type if file is null
            'alt_text' => "Cerfificat medical 2018",     //alt text for images
            'title' => "CERTIFICAT_2018",           //title of the file
        ];
        $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', $default);

        $this->assertDatabaseHas('attachments', [
            'attachable_id' => $user->id,
            'attachable_type' => User::class
        ]);
       
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

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $user->id
        ]);
    //TODO: Verify anything that is being added roles,groups...
    }



    //Guard check
    public function testAuthDeleteInvalidNotLogged() {
        $response = $this->delete('api/auth/delete');
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => __('auth.login_required')]);
    }
}