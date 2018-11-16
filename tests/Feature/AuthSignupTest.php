<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Artisan;
use App\User;
use App\Account;

class AuthSignupTest extends TestCase {

    //Database setup
    public function setUp() {
        parent::setUp();
        
        Mail::fake();        //Avoid sending emails
        Artisan::call('migrate');
        //$this->loginAs();   //Create user and login and get current user in $this->user
    }

    //Clean up after the test
    public function tearDown() {
        parent::tearDown();

    }

    ////////////////////////////////////////////////////////////////////////
    // Parameters testing
    ////////////////////////////////////////////////////////////////////////
    public function testSignupInvalidEmailAddress() {
        $data = [
            'email' => 'toto',
            'firstName' => 'titi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];
        $response = $this->post('api/auth/signup', $data);
        //dd($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }

    public function testSignupInvalidPhone() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'titi',
            'lastName' => 'Redorta',
            'mobile' => '0623aa133213',
            'password'=> 'Secure0'            
        ];
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }

    public function testSignupInvalidMissingfirstName() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }    

    public function testSignupInvalidMissingLastName() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'Sergi',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }

    public function testSignupInvalidMissingEmail() {
        $data = [
            'lastName' => 'redorta',
            'firstName' => 'Sergi',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];
        $response = $this->post('api/auth/signup', $data);
        //dd ($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }    

    public function testSignupInvalidMissingAll() {
        $data = [];
        $response = $this->post('api/auth/signup', $data);
        //dd ($response->json());
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'validation_failed']);
    }   

    public function testSignupAlreadyRegistered() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'signup_success']);
        //Recreate same user
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'user_already_registered']);
        //Check phone
        $data['email'] = 'sergi.redorta2@hotmail.com';
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'user_already_registered']);
        //Check email
        $data['phone'] = '0623133222';
        $data['email'] = 'sergi.redorta@hotmail.com';
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(400)->assertJson(['response'=>'error', 'message'=>'user_already_registered']);
        //dd ($response->json());
    }

    ////////////////////////////////////////////////////////////////////////
    // Check that we can insert element in database
    ////////////////////////////////////////////////////////////////////////
    public function testSignupSuccess() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(200)->assertJson(['response'=>'success', 'message'=>'signup_success']);        
        $this->assertDatabaseHas('users', [
            'email' => 'sergi.redorta@hotmail.com'
        ]);
    }    

    ////////////////////////////////////////////////////////////////////////
    // Database checks
    ////////////////////////////////////////////////////////////////////////
    public function testSignupUserCreated() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);
        $this->assertDatabaseHas('users', [
            'email' => 'sergi.redorta@hotmail.com', 'isEmailValidated' => false
        ]);
    }

    public function testSignupDefaultAccountCreated() {
        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);
        $user = User::all()->last();
        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'access' => 'Pré-inscrit'
        ]);
    }
    ////////////////////////////////////////////////////////////////////////
    // GUARDS TEST
    ////////////////////////////////////////////////////////////////////////
    public function testSignupAlreadyLoggedIn() {
        $this->loginAs();

        $data = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133213',
            'password'=> 'Secure0'            
        ];        
        $response = $this->post('api/auth/signup', $data);
        $response->assertStatus(401)->assertJson(['response'=>'error', 'message'=>'already_loggedin']); 
    }    
    
}