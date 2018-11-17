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
use App\Notification;

class NotificationsTest extends TestCase {

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
/*Route::delete('notifications/delete', 'NotificationController@delete');       #id
    Route::post('notifications/markread', 'NotificationController@markAsRead');  #id
    Route::get('notifications/getAll', 'NotificationController@getAll');*/
    ////////////////////////////////////////////////////////////////////////
    // Parameters testing
    ////////////////////////////////////////////////////////////////////////

    public function testNotificationsInvalidId() {
        $this->loginAs();
        $response = $this->delete('api/notifications/delete', ["test"=>"test"]);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation_failed']);
    }

    public function testNotificationsInvalidIdNumber() {
        $this->loginAs();
        $response = $this->delete('api/notifications/delete', ["id"=>100]);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation_failed']);
    }

    public function testNotificationsNotLoggedIn() {
        $response = $this->delete('api/notifications/delete', ["id"=>1]);
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'not_loggedin']);
    }

    public function testNotificationsOnSignUp() {
        $this->loginAs();
        $user = User::all()->last();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id
        ]);
    }

    public function testNotificationsValidDelete() {
        $this->loginAs();
        $response = $this->delete('api/notifications/delete', ["id"=>1]);
        $user = User::all()->last();
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $user->id
        ]);
    }

    public function testNotificationsMarkAsReadValid() {
        $this->loginAs();
        $user = User::all()->last();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'isRead' => false
        ]);
        $response = $this->post('api/notifications/markread', ["id"=>1]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'isRead' => true
        ]);
    }

    public function testNotificationsMarkAsReadInValidId() {
        $this->loginAs();
        $user = User::all()->last();
        $response = $this->post('api/notifications/markread', ["id"=>10]);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation_failed']);
    }

    public function testNotificationsMarkAsReadInMissingId() {
        $this->loginAs();
        $user = User::all()->last();
        $response = $this->post('api/notifications/markread', ["test"=>10]);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation_failed']);
    }    

    public function testNotificationsMarkAsReadNotLoggedIn() {
        $this->loginAs();
        $this->logout();
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenBlacklistedException::class);
        $response = $this->post('api/notifications/markread', ["id"=>1]);
        dd($response->json());
    }      


    public function testNotificationsgetAllNotLoggedIn() {
        $this->loginAs();
        $this->logout();
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenBlacklistedException::class);
        $response = $this->get('api/notifications/getAll');
        dd($response->json());
    }      

    public function testNotificationsgetAllValid() {
        $this->loginAs();
        $user = User::all()->last();
        $notification = new Notification;
        $notification->user_id = 1;
        $notification->text = "Test of notif";
        $notification->save();
        $response = $this->get('api/notifications/getAll');
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id, "text" => "Test of notif"
        ]);
        $this->assertDatabaseHas('notifications', [
            'id'=>1, 'user_id' => $user->id
        ]);
    } 
    public function testNotificationsgetAllNotLoggedInTwo() {
        $response = $this->get('api/notifications/getAll');
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'not_loggedin']);
    } 


}



