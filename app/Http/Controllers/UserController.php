<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\User;
use App\Account;
use App\kubiikslib\Helper;
use App\kubiikslib\AuthTrait;
use JWTAuth;

class UserController extends Controller
{
    use AuthTrait;
    //
    public function index() {
        //return User::all();
        return response()->json([
            'message' => TrialAbstract::test()
        ],200);        
    }

    public function test() {
        $key = $this->generatePassword();
        echo $key;
        $time = $this->getTokenLife(false);
        echo $time;
/*
        $account = Account::find(1);
        $myret = false;
        if (Hash::check('Secure0', $account->password)) {
            $myret = true;
        }
        $credentials = ['id' => $account->id, 'password'=> 'Secure0'];
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'response' => 'error',
                'message' => 'invalid_email_or_password',
                'myret' => $myret
            ],401);            
        } else {
            return response()->json([
                'response' => 'success',
                'message' => $token,
                'myret' => Helper::generateRandomPassword(5)
            ],401);              
        }*/
    }
}
