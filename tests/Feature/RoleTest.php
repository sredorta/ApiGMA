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


    public function testRoleCreateInvalidParams() { 
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/roles/create', []);
        $response->assertStatus(400)->assertJson(['response' => 'error', 'message' => 'validation.required']);
    }
    

    public function testRoleCreateValid() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/roles/create', ['isUnique'=>true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(200)->assertJson(['id' => 1, 'isUnique' => true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
    }

    //Delete tests
    public function testRoleDeleteInvalidId() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);

        $response = $this->delete('api/roles/delete', ['id'=>10]);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']);        
    }

    public function testRoleDeleteInvalidIdFormat() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);

        $response = $this->delete('api/roles/delete', ['id'=>'test']);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.numeric']);        
    }

    public function testRoleDeleteValidNotUnique() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/attach', ['role_id'=>1, 'user_id'=>1]);
        $response  = $this->post('api/roles/attach', ['role_id'=>1, 'user_id'=>2]);
        $response  = $this->post('api/roles/attach', ['role_id'=>1, 'user_id'=>3]);
        $this->assertDatabaseHas('roles', [
            'id'=>1, 'name' => 'test'
        ]);
        $this->assertDatabaseHas('role_user', [
            'role_id'=>1, 'user_id' => 1
        ]);
        $this->assertDatabaseHas('role_user', [
            'role_id'=>1, 'user_id' => 2
        ]);

        $response = $this->delete('api/roles/delete', ['id'=>1]);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('roles', [
            'id'=>1, 'name' => 'test'
        ]);
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 1
        ]);
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 2
        ]);                
    }

    //Attach tests
    public function testRoleAttachInValidRoleIdNumber() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/attach', ['role_id'=>10, 'user_id'=>1]);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']); 
    }

    public function testRoleAttachInValidRoleIdFormat() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/attach', ['role_id'=>'test', 'user_id'=>1]);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.numeric']); 
    }    
    public function testRoleAttachInValidUserIdNumber() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/attach', ['role_id'=>1, 'user_id'=>10]);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']); 
    }
    
    public function testRoleAttachInValidUserIdFormat() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/attach', ['role_id'=>1, 'user_id'=>'test']);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.numeric']); 
    }  

    public function testRoleAttachInValidParameters() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/attach', ['test'=>'test']);
        $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.required']); 
    } 

    public function testRoleAttachValidUnique() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/attach', ['user_id'=>1, 'role_id'=>1]);
        $response->assertStatus(204); 
        $this->assertDatabaseHas('role_user', [
            'role_id'=>1, 'user_id' => 1
        ]);  
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 2
        ]);  
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 3
        ]);  
        $response  = $this->post('api/roles/attach', ['user_id'=>3, 'role_id'=>1]);
        $response->assertStatus(204); 
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 1
        ]);  
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 2
        ]);  
        $this->assertDatabaseHas('role_user', [
            'role_id'=>1, 'user_id' => 3
        ]);  
    }

    public function testRoleAttachValidNotUnique() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/roles/attach', ['user_id'=>1, 'role_id'=>1]);
        $response->assertStatus(204); 
        $this->assertDatabaseHas('role_user', [
            'role_id'=>1, 'user_id' => 1
        ]);  
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 2
        ]);  
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 3
        ]);  
        $response  = $this->post('api/roles/attach', ['user_id'=>3, 'role_id'=>1]);
        $response->assertStatus(204); 
        $this->assertDatabaseHas('role_user', [
            'role_id'=>1, 'user_id' => 1
        ]);  
        $this->assertDatabaseMissing('role_user', [
            'role_id'=>1, 'user_id' => 2
        ]);  
        $this->assertDatabaseHas('role_user', [
            'role_id'=>1, 'user_id' => 3
        ]);  
    }

        //Detach tests
        public function testRoleDetachInValidRoleIdNumber() {
            $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
            $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
            $this->loginAsMultiple([
                'email' => 'sergi.redorta@hotmail.com',
                'password'=> 'Secure2',
                'access' => Config::get('constants.ACCESS_ADMIN')]);
            
            $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
            $response  = $this->post('api/roles/detach', ['role_id'=>10, 'user_id'=>1]);
            $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.exists']); 
        }
    
        public function testRoleDetachInValidRoleIdFormat() {
            $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
            $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
            $this->loginAsMultiple([
                'email' => 'sergi.redorta@hotmail.com',
                'password'=> 'Secure2',
                'access' => Config::get('constants.ACCESS_ADMIN')]);
            
            $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
            $response  = $this->post('api/roles/detach', ['role_id'=>'test', 'user_id'=>1]);
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
        
        public function testRoleDetachInValidUserIdFormat() {
            $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
            $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
            $this->loginAsMultiple([
                'email' => 'sergi.redorta@hotmail.com',
                'password'=> 'Secure2',
                'access' => Config::get('constants.ACCESS_ADMIN')]);
            
            $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
            $response  = $this->post('api/roles/detach', ['role_id'=>1, 'user_id'=>'test']);
            $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.numeric']); 
        }  
    
        public function testRoleDetachInValidParameters() {
            $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
            $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
            $this->loginAsMultiple([
                'email' => 'sergi.redorta@hotmail.com',
                'password'=> 'Secure2',
                'access' => Config::get('constants.ACCESS_ADMIN')]);
            
            $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
            $response  = $this->post('api/roles/detach', ['test'=>'test']);
            $response->assertStatus(400)->assertJson(['response' => 'error','message' => 'validation.required']); 
        } 
    
        public function testRoleDetachValid() {
            $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
            $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
            $this->loginAsMultiple([
                'email' => 'sergi.redorta@hotmail.com',
                'password'=> 'Secure2',
                'access' => Config::get('constants.ACCESS_ADMIN')]);
            
            $response = $this->post('api/roles/create', ['isUnique'=>true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
            $response  = $this->post('api/roles/attach', ['user_id'=>1, 'role_id'=>1]);
            $response->assertStatus(204); 
            $this->assertDatabaseHas('role_user', [
                'role_id'=>1, 'user_id' => 1
            ]);  
            $this->assertDatabaseMissing('role_user', [
                'role_id'=>1, 'user_id' => 2
            ]);  
            $this->assertDatabaseMissing('role_user', [
                'role_id'=>1, 'user_id' => 3
            ]);  
            $response  = $this->post('api/roles/detach', ['user_id'=>1, 'role_id'=>1]);
            $response->assertStatus(204); 
            $this->assertDatabaseMissing('role_user', [
                'role_id'=>1, 'user_id' => 1
            ]);  
            $this->assertDatabaseMissing('role_user', [
                'role_id'=>1, 'user_id' => 2
            ]);  
            $this->assertDatabaseMissing('role_user', [
                'role_id'=>1, 'user_id' => 3
            ]);  
        }
    //Get tests
    public function testRoleGetValidEmpty() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response  = $this->get('api/roles');
        $response->assertStatus(200)->assertExactJson([]); 
    }
    public function testRoleGetValidOne() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        
        $response = $this->post('api/roles/create', ['isUnique'=>true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->get('api/roles');
        $response->assertStatus(200)->assertJsonFragment(['id'=>1, 'isUnique'=>"1", 'name'=>'test']);
    }
    public function testRoleGetValidTwo() {
        $this->signup(['email'=> 'sergi.redorta2@hotmail.com', 'mobile' => '0623133222']);
        $this->signup(['email'=> 'sergi.redorta3@hotmail.com', 'mobile' => '0623133233']);
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure2',
            'access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->post('api/roles/create', ['isUnique'=>true, 'name'=>'test1', 'description'=>'This is a long description because if not will fail']);
        $response = $this->post('api/roles/create', ['isUnique'=>false, 'name'=>'test2', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->get('api/roles');
        $response->assertStatus(200)->assertJsonFragment(['id'=>1, 'isUnique'=>"1", 'name'=>'test1']);
        $response->assertStatus(200)->assertJsonFragment(['id'=>2, 'isUnique'=>"0", 'name'=>'test2']);
    }
    //////////////////////////////////////////////////////////////
    //  Guard check
    //////////////////////////////////////////////////////////////
    public function testRoleCreateInValidLoggedInAsDefault() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0',
            'access' => Config::get('constants.ACCESS_DEFAULT')]);

        $response = $this->post('api/roles/create', ['isUnique'=>true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }

    public function testRoleCreateInValidLoggedInAsMember() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure1',
            'access' => Config::get('constants.ACCESS_MEMBER')]);

        $response = $this->post('api/roles/create', ['isUnique'=>true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }    

    public function testRoleCreateInValidNotLoggedIn() {
        $response = $this->post('api/roles/create', ['isUnique'=>true, 'name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.login_required')]);
    }   


    public function testRoleDeleteInValidLoggedInAsDefault() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0',
            'access' => Config::get('constants.ACCESS_DEFAULT')]);

        $response = $this->delete('api/roles/delete', ['id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }

    public function testRoleDeleteInValidLoggedInAsMember() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure1',
            'access' => Config::get('constants.ACCESS_MEMBER')]);

        $response = $this->delete('api/roles/delete', ['id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }    

    public function testRoleDeleteInValidNotLoggedIn() {
        $response = $this->delete('api/roles/delete', ['id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.login_required')]);
    }   


    public function testRoleAttachInValidLoggedInAsDefault() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0',
            'access' => Config::get('constants.ACCESS_DEFAULT')]);

        $response = $this->post('api/roles/attach', ['user_id'=>1, 'role_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }

    public function testRoleAttachInValidLoggedInAsMember() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure1',
            'access' => Config::get('constants.ACCESS_MEMBER')]);

        $response = $this->post('api/roles/attach', ['user_id'=>1, 'role_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }    

    public function testRoleAttachInValidNotLoggedIn() {
        $response = $this->post('api/roles/attach', ['user_id'=>1, 'role_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.login_required')]);
    } 


    public function testRoleDetachInValidLoggedInAsDefault() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure0',
            'access' => Config::get('constants.ACCESS_DEFAULT')]);

        $response = $this->post('api/roles/detach', ['user_id'=>1, 'role_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }

    public function testRoleDetachInValidLoggedInAsMember() {
        $this->loginAsMultiple([
            'email' => 'sergi.redorta@hotmail.com',
            'password'=> 'Secure1',
            'access' => Config::get('constants.ACCESS_MEMBER')]);

        $response = $this->post('api/roles/detach', ['user_id'=>1, 'role_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.admin_required')]);
    }    

    public function testRoleDetachInValidNotLoggedIn() {
        $response = $this->post('api/roles/detach', ['user_id'=>1, 'role_id'=>1]);
        $response->assertStatus(401)->assertJson(['response' => 'error','message' => __('auth.login_required')]);
    }     

}
