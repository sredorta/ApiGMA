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

class RoleTest extends TestCase {

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
/*    Route::get('roles' , 'RoleController@getRoles');              //Get all Roles
    Route::post('roles/attach' , 'RoleController@attachUser');    //Adds a role to a user
    Route::post('roles/detach' , 'RoleController@detachUser');    //Removes a role to a user
    Route::post('roles/create' , 'RoleController@create');        //Creates a new role
    Route::post('roles/delete' , 'RoleController@delete');        //Deletes a role*/

    //Guard check
    public function testRoleCreateValid() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);

        $response = $this->post('api/roles/create', ['isUnique'=>true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(200)->assertJson(['id' => 1, 'isUnique' => true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
    }


}
