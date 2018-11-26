<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\User;
use App\Account;
use App\kubiikslib\Helper;
use App\kubiikslib\AuthTrait;
use JWTAuth;
use App;

use Intervention\Image\ImageManager;

class UserController extends Controller
{
    use AuthTrait;
 
}
