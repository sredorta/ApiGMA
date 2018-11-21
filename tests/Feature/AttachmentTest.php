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
        Storage::disk('public')->deleteDirectory('images');
    }


    private function callController($data = []) {
        $path = dirname(__DIR__) . '/storage/test_files/test.jpg';
        $file = new UploadedFile($path, 'test.jpg', filesize($path), 'image/jpeg', null, true);
        $default = [
            'id' => 50,
            'type' => 'dummy',
            'image' => $file
        ];
        return $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', array_merge($default, $data));
    }
    public function testAttachmentIncorrectDataEmpty() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->callController();
        //$response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', []);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);  //expected status*/
    }

    public function testAttachmentIncorrectDataInvalidType() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', ['id'=> $user->id, 'type'=>'toto']);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'attachment.wrong_type']);  //expected status*/
    }

    public function testAttachmentIncorrectDataInvalidId() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', ['id'=> 20, 'type'=>User::class]);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'attachment.wrong_id']);  //expected status*/
    }





    public function testAttachmentIncorrectData() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        //Open image and get base64
        //dd(Storage::disk('public')->exists('test_files/test.jpg' ));
        //Storage::disk('public')->put($path . $filename . "/orig.jpeg", $stream);

        $image = base64_encode(Storage::disk('public')->get('test_files/testvertical.jpg' )); //Need to assert if file not exists
        $image = "data:image/jpeg;base64," . $image;
        $pdf = base64_encode(Storage::disk('public')->get('test_files/test.pdf' ));
        $pdf = "data:image/jpeg;base64," . $pdf;

        $user = User::all()->last();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/attachment/create', []);
        dd($response->json());

        $attachment = new Attachment;
        $attachment = $attachment->add([
            'id' => $user->id, 
            'type' => User::class, 
            'default' => 'avatar',
            'root' => "images", 
            'alt_text' => 'this is my alt text',
            'title' => 'this is my tytle',
            'filedata' => $image,
        ]);         
        dd(Attachment::all()->last()->toArray());
        //dd(Thumb::all());
        //dd(Attachment::all());
/*
        $response = $this->post('api/user/attachment/add', [
            'attachable_type' => 'toto',
            'attachable_id' => '3'
        ]);
        dd($response->json());
        //$response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);  //expected status*/
    }

}