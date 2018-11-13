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

 
}
