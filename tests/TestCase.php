<?php

//We put here all reusable code
//We will put here all the code for login as different access and more

namespace Tests;


use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Artisan;
use App\User;
use App\Account;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    protected $token = null;    //Stores the token
    protected $user = null;     //Stores the auth user


    /////////////////////////////////////////////////////////////////////////////////
    //Creates an user and updates email as valid
    /////////////////////////////////////////////////////////////////////////////////
    protected function signup($data = []) {
        $default = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'Sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133212',
            'password'=> 'Secure0'            
        ];
        $response = $this->post('api/auth/signup', array_merge($default, $data));
        $response->assertStatus(200);

        //We now update isEmailValidated to get access to the user
        if (User::all()->count()>0) {
            $user = User::all()->last();
            $user->isEmailValidated = true;
            $user->update();  
        }

        return $response;   //We return resonse for tests of Auth      
    }

    /////////////////////////////////////////////////////////////////////////////////
    //Login to the specified user
    /////////////////////////////////////////////////////////////////////////////////
    protected function login($data = []) {
        $default = [
            'email' => 'sergi.redorta@hotmail.com',
            'password' => 'Secure0',
            'keepconnected' => false
        ];
        //Now we login and get the token
        $response = $this->post('api/auth/login', array_merge($default, $data));
        $response->assertStatus(200);  //expected status

        //Now get the Auth user
        $result = $response->json();
        $token = $result['token']; //This is our response 
        $this->token = $token;
    }

    /////////////////////////////////////////////////////////////////////////////////
    //Get authenticated user
    /////////////////////////////////////////////////////////////////////////////////
    protected function getAuthUser() {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $result = $response->json();
        $user = User::find($result['id']); 
        $this->user = $user;      
    }

    /////////////////////////////////////////////////////////////////////////////////
    //Create a user and login and return the authenticated user
    /////////////////////////////////////////////////////////////////////////////////
    protected function loginAs($data = []) {
        $default = [
            'email' => 'sergi.redorta@hotmail.com',
            'firstName' => 'Sergi',
            'lastName' => 'Redorta',
            'mobile' => '0623133212',
            'password'=> 'Secure0'
        ];        
        $this->signup(array_merge($default, $data));
        $this->login(array_merge($default, $data));
        return $this->getAuthUser();
    }


    protected function trial() {
        echo 'In the abstract';
    }





}
