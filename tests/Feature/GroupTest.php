<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
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
use App\Message;

class GroupTest extends TestCase {

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



    public function testRoleCreateInvalidParams() { 
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/roles/create', []);
        $response->assertStatus(400)->assertJson(['response' => 'error', 'message' => 'validation.required']);
    }
    

    public function testGroupCreateValid() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(200)->assertJson(['id' => 1,'name'=>'test', 'description'=>'This is a long description because if not will fail']);
    }

    //Delete tests
    public function testGroupDeleteInvalidId() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);

        $response = $this->delete('api/groups/delete', ['id'=>10]);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']);        
    }

    public function testGroupDeleteInvalidIdFormat() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);

        $response = $this->delete('api/groups/delete', ['id'=>'test']);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.numeric']);        
    }


   //Attach tests
   public function testGroupAttachInValidIdNumber() {
    $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
    $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
    $this->loginAsMultiple([
        'email' => 'sergi.redorta@hotmail.com',
        'password'=> 'Secure2',
        'access' => Config::get('constants.ACCESS_ADMIN')]);
    
    $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
    $response  = $this->post('api/groups/attach', ['group_id'=>10, 'user_id'=>1]);
    $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']); 
}

public function testGroupAttachInValidIdFormat() {
    $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
    $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
    $this->loginAsMultiple([
        'email' => 'sergi.redorta@hotmail.com',
        'password'=> 'Secure2',
        'access' => Config::get('constants.ACCESS_ADMIN')]);
    
    $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
    $response  = $this->post('api/groups/attach', ['group_id'=>'test', 'user_id'=>1]);
    $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.numeric']); 
}    

public function testGroupAttachInValidUserIdNumber() {
    $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
    $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
    $this->loginAsMultiple([
        'email' => 'sergi.redorta@hotmail.com',
        'password'=> 'Secure2',
        'access' => Config::get('constants.ACCESS_ADMIN')]);
    
    $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
    $response  = $this->post('api/groups/attach', ['group_id'=>1, 'user_id'=>10]);
    $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']); 
}


public function testRoleAttachValidUnique() {
    $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
    $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
    $this->loginAsMultiple([
        'email' => 'sergi.redorta@hotmail.com',
        'password'=> 'Secure2',
        'access' => Config::get('constants.ACCESS_ADMIN')]);
    
    $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
    $response  = $this->post('api/groups/attach', ['user_id'=>1, 'group_id'=>1]);
    $response->assertStatus(204); 
    $this->assertDatabaseHas('group_user', [
        'group_id'=>1, 'user_id' => 1
    ]);  
    $this->assertDatabaseMissing('group_user', [
        'group_id'=>1, 'user_id' => 2
    ]);  
    $this->assertDatabaseMissing('group_user', [
        'group_id'=>1, 'user_id' => 3
    ]);  
}

       //Detach tests
       public function testGroupDetachInValidIdNumber() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/groups/detach', ['group_id'=>10, 'user_id'=>1]);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']); 
    }

    public function testGroupDetachInValidIdFormat() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/groups/detach', ['group_id'=>'test', 'user_id'=>1]);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.numeric']); 
    }    
    public function testRoleDetachInValidUserIdNumber() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/detach', ['role_id'=>1, 'user_id'=>10]);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']); 
    }
    

    public function testGroupDetachValid() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/groups/attach', ['user_id'=>1, 'group_id'=>1]);
        $response->assertStatus(204); 
        $this->assertDatabaseHas('group_user', [
            'group_id'=>1, 'user_id' => 1
        ]);  
        $this->assertDatabaseMissing('group_user', [
            'group_id'=>1, 'user_id' => 2
        ]);  
        $this->assertDatabaseMissing('group_user', [
            'group_id'=>1, 'user_id' => 3
        ]);  
    }

    //Get tests
    public function testGroupGetValidEmpty() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response  = $this->get('api/groups');
        $response->assertStatus(200)->assertExactJson([]); 
    }
    public function testGroupGetValidOne() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->get('api/groups');
        $response->assertStatus(200)->assertJsonFragment(['id'=>1,'name'=>'test']);
    }

    public function testGroupGetValidTwo() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->post('api/groups/create', ['name'=>'test1', 'description'=>'This is a long description because if not will fail']);
        $response = $this->post('api/groups/create', [ 'name'=>'test2', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->get('api/groups');
        $response->assertStatus(200)->assertJsonFragment(['id'=>1,  'name'=>'test1']);
        $response->assertStatus(200)->assertJsonFragment(['id'=>2,  'name'=>'test2']);
    }
    public function testGroupUserDeleteNoDataLeft() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->post('api/groups/create', ['name'=>'test1', 'description'=>'This is a long description because if not will fail']);
        $response = $this->post('api/groups/create', ['name'=>'test2', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/groups/attach', ['user_id'=>1, 'group_id'=>1]);
        $response  = $this->post('api/groups/attach', ['user_id'=>2, 'group_id'=>2]);
        $response  = $this->post('api/groups/attach', ['user_id'=>2, 'group_id'=>1]);

        User::find(1)->delete();
        $this->assertDatabaseMissing('group_user', [
            'group_id'=>1, 'user_id' => 1
        ]);  
        $this->assertDatabaseHas('group_user', [
            'group_id'=>1, 'user_id' => 2
        ]);          
    }

    //////////////////////////////////////////////////////////////
    //  Guard check
    //////////////////////////////////////////////////////////////
    //public function testGroupGetAll

    public function testGroupCreateInValidLoggedInAsDefault() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0',
            'access' => Config::get('constants.ACCESS_DEFAULT')]);

        $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }

    public function testGroupCreateInValidLoggedInAsMember() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure1',
            'access' => Config::get('constants.ACCESS_MEMBER')]);

        $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }    

    public function testGroupsCreateInValidNotLoggedIn() {
        $response = $this->post('api/roles/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.login_required')]);
    }   


    public function testGroupDeleteInValidLoggedInAsDefault() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0',
            'access' => Config::get('constants.ACCESS_DEFAULT')]);

        $response = $this->delete('api/groups/delete', ['id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }

    public function testGroupDeleteInValidLoggedInAsMember() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure1',
            'access' => Config::get('constants.ACCESS_MEMBER')]);

        $response = $this->delete('api/groups/delete', ['id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }    

    public function testGroupDeleteInValidNotLoggedIn() {
        $response = $this->delete('api/groups/delete', ['id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.login_required')]);
    }   


    public function testGroupAttachInValidLoggedInAsDefault() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0',
            'access' => Config::get('constants.ACCESS_DEFAULT')]);

        $response = $this->post('api/groups/attach', ['user_id'=>1, 'group_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }

    public function testGroupAttachInValidLoggedInAsMember() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure1',
            'access' => Config::get('constants.ACCESS_MEMBER')]);

        $response = $this->post('api/groups/attach', ['user_id'=>1, 'group_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }    

    public function testGroupsAttachInValidNotLoggedIn() {
        $response = $this->post('api/roles/attach', ['user_id'=>1, 'group_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.login_required')]);
    } 


    public function testGroupDetachInValidLoggedInAsDefault() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0',
            'access' => Config::get('constants.ACCESS_DEFAULT')]);

        $response = $this->post('api/groups/detach', ['user_id'=>1, 'group_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }

    public function testGroupDetachInValidLoggedInAsMember() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure1',
            'access' => Config::get('constants.ACCESS_MEMBER')]);

        $response = $this->post('api/groups/detach', ['user_id'=>1, 'group_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }    

    public function testGroupDetachInValidNotLoggedIn() {
        $response = $this->post('api/groups/detach', ['user_id'=>1, 'group_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.login_required')]);
    }     



}
