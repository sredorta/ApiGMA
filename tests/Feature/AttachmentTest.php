<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $attachment = new Attachment;
        $attachment = $attachment->add($user->id, User::class,'avatar','images', "Test alt text", $image);         
        //public function add($id, $type, $function, $root, $alt_text, $filedata) {     

        dd(Thumb::all());
        dd(Attachment::all());
/*
        $response = $this->post('api/user/attachment/add', [
            'attachable_type' => 'toto',
            'attachable_id' => '3'
        ]);
        dd($response->json());
        //$response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation.required']);  //expected status*/
    }

}