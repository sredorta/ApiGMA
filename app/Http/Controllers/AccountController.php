<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Account;
use App\kubiikslib\Helper;
use App\kubiikslib\AuthTrait;
use JWTAuth;

class AccountController extends Controller
{
    use AuthTrait;  //Use Auth trait all logic is in the trait for reuse !

}
