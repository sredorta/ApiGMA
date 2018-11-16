<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Artisan;
use App\User;
use App\Account;

class AttachmentTest extends TestCase {

    //Database setup
    public function setUp() {
        parent::setUp();
        
        Mail::fake();        //Avoid sending emails
        Artisan::call('migrate');
        $this->loginAs();   //Create user and login and get current user in $this->user
    }
/*
    public function testSignup() {

        //echo $this->user->accounts()->get();        

    }*/

/*

    public function testLogin() {



        $response = $this->post('api/auth/login', [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0',
            'keepconnected' => false
        ]);
        dd(User::where('email', 'sergi.redorta@hotmail.com')->get()->first());
        dd($response->json()); //This is our response

        $response->assertStatus(200);  //expected status
    }*/

/*
    public function testIncorrectData() {
        $response = $this->post('api/user/document/add', [
            'attachable_type' => 'toto',
            'attachable_id' => '3'
        ]);
        $response->assertStatus(400);  //expected status
    }*/

}