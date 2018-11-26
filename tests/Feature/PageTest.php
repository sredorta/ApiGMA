<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\kubiikslib\Helper;
use Artisan;
use App\User;
use App\Page;

class PageTest extends TestCase {

    //Database setup
    public function setUp() {
        parent::setUp();
        
        Mail::fake();        //Avoid sending emails
        Artisan::call('migrate');
        $this->cleanDirectories();
    }

    //Clean up after the test
    public function tearDown() {
        parent::tearDown();
        $this->cleanDirectories();
    }


    public function cleanDirectories () {
        Storage::disk('public')->deleteDirectory('uploads');
    }
    public function getFileForAttachment($attachment) {
        return dirname(__DIR__) . '/storage/uploads/' . $attachment['file_name'];
    }

    public function getAttachedThumb($attachment) {
        return dirname(__DIR__) . '/storage/uploads/medium/' . $attachment['file_name'];
    }

    public function getFileDefault($type) {
        switch ($type) {
            case "avatar":
                return dirname(__DIR__) . '/storage/defaults/userdefault.jpg';
                break;
            default: return null;
        }
    }

    private function callController($testfile, $data = []) {
            $path = dirname(__DIR__) . '/storage/test_files/' . $testfile;
            $mime = Storage::disk('public')->mimeType('/test_files/' . $testfile);
            $file = new UploadedFile($path, $testfile, filesize($path), $mime, null, true);       
            $default = [
                'attachable_id' => 50,                            
                'attachable_type' => 'dummy',              
                'file' => $file,                        //Uploaded file
                //'default' => 'avatar',                  //Default type if file is null
                'alt_text' => "Alt text for image",     //alt text for images
                'title' => "Title for image",           //title of the file
            ];
        return $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', array_merge($default, $data));
    }



    public function testPageCreateValid() { 
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/pages/create', ['content'=>'<div>test</div>']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('pages', [
            'id'=>1, 'content' => '<div>test</div>'
        ]);
    }

    public function testPageGetAllValid() { 
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/pages/create', ['content'=>'<div>test</div>']);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/pages/create', ['content'=>'<div>test</div>']);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/pages', ['content'=>'<div>test</div>']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('pages', [
            'id'=>1, 'content' => '<div>test</div>'
        ]);
        $this->assertDatabaseHas('pages', [
            'id'=>2, 'content' => '<div>test</div>'
        ]);        
    }

    public function testPageAttachmentsValid() { 
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/pages/create', ['content'=>'<div>test</div>']);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/pages/create', ['content'=>'<div>test</div>']);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/pages', ['content'=>'<div>test</div>']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('pages', [
            'id'=>1, 'content' => '<div>test</div>'
        ]);
        $this->assertDatabaseHas('pages', [
            'id'=>2, 'content' => '<div>test</div>'
        ]);        
        $testfile = 'testvertical.jpg';
        $path = dirname(__DIR__) . '/storage/test_files/' . $testfile;
        $mime = Storage::disk('public')->mimeType('/test_files/' . $testfile);
        $file = new UploadedFile($path, $testfile, filesize($path), $mime, null, true);       
        $default = [
            'attachable_id' => 1,                            
            'attachable_type' => Page::class,              
            'file' => $file,                        //Uploaded file
            'alt_text' => "Alt text for image",     //alt text for images
            'title' => "Title for image",           //title of the file
        ];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', $default);   
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', $default);
        $this->assertDatabaseHas('attachments', [
            'id'=>2, 'attachable_id'=>1, 'attachable_type' => Page::class
        ]);
        $this->assertDatabaseHas('attachments', [
            'id'=>3, 'attachable_id'=>1, 'attachable_type' => Page::class
        ]);
    
    }



/*

//PAGE HANDLING
    Route::get('pages', 'PageController@getAll'); //MOVE ME TO ANY !!!!
    Route::delete('pages/delete', 'PageController@delete');
    Route::post('pages/create', 'PageController@create');
    Route::get('pages/attachments', 'PageController@getAttachments'); //MOVE ME TO ANY !!!!
    Route::post('pages/attachments/create', 'PageController@addAttachment');*/
}