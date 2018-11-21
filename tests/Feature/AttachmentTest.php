<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Artisan;
use App\User;
use App\Account;
use App\Attachment;
use App\Thumb;

class AttachmentTest extends TestCase {

    //Database setup
    public function setUp() {
        parent::setUp();
        Mail::fake();        //Avoid sending emails
        Artisan::call('migrate');
        $this->cleanDirectories();
    }

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
        if ($testfile!==null) {
            $path = dirname(__DIR__) . '/storage/test_files/' . $testfile;
            $mime = Storage::disk('public')->mimeType('/test_files/' . $testfile);
            $file = new UploadedFile($path, $testfile, filesize($path), $mime, null, true);       
            $default = [
                'attachable_id' => 50,                            
                'attachable_type' => 'dummy',              
                'file' => $file,                        //Uploaded file
                'default' => 'avatar',                  //Default type if file is null
                'alt_text' => "Alt text for image",     //alt text for images
                'title' => "Title for image",           //title of the file
                'root' => 'images'                      //root directory where to place file : TODO protect with some data checks
            ];
        } else {
            $path = dirname(__DIR__) . '/storage/test_files/test.jpg';
            $file = new UploadedFile($path, 'test.jpg', filesize($path), 'image/jpeg', null, true);       
            $default = [
                'attachable_id' => 50,                            
                'attachable_type' => 'dummy',              
                'default' => 'avatar',                  //Default type if file is null
                'alt_text' => "Alt text for image",     //alt text for images
                'title' => "Title for image",           //title of the file
                'root' => 'images'                      //root directory where to place file : TODO protect with some data checks
            ];
        }
        return $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', array_merge($default, $data));
    }


    public function testAttachmentCreateIncorrectDataInvalidType() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('test.jpg',['attachable_id'=> $user->id]);

        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'attachment.wrong_type']);  //expected status*/
    }

    public function testAttachmentCreateIncorrectDataInvalidId() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('test.jpg',['attachable_id'=> 20, 'attachable_type'=> User::class]);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'attachment.wrong_id']);  //expected status*/
    }


    public function testAttachmentCreateInCorrectFile() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('test.jpg',['attachable_id'=> 20, 'attachable_type'=> User::class, 'file'=> 'toto']);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.mimes']);  //expected status*/
    }

    public function testAttachmentCreateCorrectFile() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('test.jpg',['attachable_id'=> 1, 'attachable_type'=> User::class]);
        $response->assertStatus(204);
        $attachment = Attachment::find(1);
        $this->assertFileExists($this->getFileForAttachment($attachment));
        //Check attachment database
        $this->assertDatabaseHas('attachments', [
            'attachable_id' => 1,
            'attachable_type' => User::class,
            'file_extension' => 'jpeg',
            'mime_type' => 'image/jpeg'
        ]);        
        //Check for thumbs
        $this->assertDatabaseHas('thumbs', [
            'attachment_id' => 1,
            'size' => 'medium',
            'width' => '285',
        ]);
        $this->assertFileExists($this->getAttachedThumb($attachment));
    }

    public function testAttachmentCreateCorrectFileImageCroppingNeeded() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('testvertical.jpg',['attachable_id'=> 1, 'attachable_type'=> User::class]);
        $response->assertStatus(204);

        $attachment = Attachment::find(1);
        $this->assertFileExists($this->getFileForAttachment($attachment));
        //Check attachment database
        $this->assertDatabaseHas('attachments', [
            'attachable_id' => 1,
            'attachable_type' => User::class,
            'file_extension' => 'jpeg',
            'mime_type' => 'image/jpeg'
        ]);        
        //Check for thumbs
        $this->assertDatabaseHas('thumbs', [
            'attachment_id' => 1,
            'size' => 'medium',
            'width' => '270',
            'height' => '360'
        ]);
        //Check thumbnail is square
        $this->assertDatabaseHas('thumbs', [
            'attachment_id' => 1,
            'size' => 'thumbnail',
            'width' => '50',
            'height' => '50'
        ]);        
        $this->assertFileExists($this->getAttachedThumb($attachment));
    }

    //Test very large image
    public function testAttachmentCreateInCorrectFileImageVeryLarge() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('testlarge.jpg',['attachable_id'=> 1, 'attachable_type'=> User::class]);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.max.file']);  //expected status*/
    }



    public function testAttachmentCreateCorrectFilePDF() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('test.pdf',['attachable_id'=> 1, 'attachable_type'=> User::class]);
        $response->assertStatus(204);

        $attachment = Attachment::find(1);
        $this->assertFileExists($this->getFileForAttachment($attachment));
        //Check attachment database
        $this->assertDatabaseHas('attachments', [
            'attachable_id' => 1,
            'attachable_type' => User::class,
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf'
        ]);  
    }    

    public function testAttachmentCreateInCorrectFilePDFTooLarge() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('testtoolarge.pdf',['attachable_id'=> 1, 'attachable_type'=> User::class]);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.max.file']);  //expected status*/
    }    

    public function testAttachmentCreateInCorrectDefault() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController(null,['attachable_id'=> 1, 'attachable_type'=> User::class, 'default'=>'toto']);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'attachment.default']);  //expected status*/
    }        

    public function testAttachmentCreateCorrectDefaultAvatar() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController(null,['attachable_id'=> 1, 'attachable_type'=> User::class, 'default'=>'avatar']);
        $response->assertStatus(204);

        $this->assertFileExists($this->getFileDefault('avatar'));
        //Check attachment database
        $this->assertDatabaseHas('attachments', [
            'attachable_id' => 1,
            'attachable_type' => User::class,
            'file_extension' => 'jpg',
            'file_name' => 'userdefault.jpg'
        ]);          
        //Check for thumbs not being generated
        $this->assertDatabaseMissing('thumbs', [
            'attachment_id' => 1,
            'size' => 'medium',
            'width' => '270',
            'height' => '360'
        ]);
    }    

    //Attachment delete tests
    public function testAttachmentDeleteCorrect() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController('test.jpg',['attachable_id'=> 1, 'attachable_type'=> User::class]);
        $attachment = Attachment::find(1);
        $thumb = $attachment->thumbs()->first();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/attachment/delete', ["attachable_id"=>1,"attachable_type"=>User::class]);
        $response->assertStatus(204);
        //Check that files have been removed
        $this->assertFileNotExists($this->getFileForAttachment($attachment));
        $this->assertFileNotExists($this->getAttachedThumb($attachment));
    }

    //Attachment delete tests
    public function testAttachmentDeleteCorrectAvatarNotDeleted() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController(null,['attachable_id'=> 1, 'attachable_type'=> User::class, 'default'=>'avatar']);
        $attachment = Attachment::find(1);
        $thumb = $attachment->thumbs()->first();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/attachment/delete', ["attachable_id"=>1,"attachable_type"=>User::class]);
        $response->assertStatus(204);
        //Check that files have been removed
        $this->assertFileNotExists($this->getFileForAttachment($attachment));
        $this->assertFileExists($this->getFileDefault('avatar'));
    }    

}