<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\User;
use App\Account;
use App\kubiikslib\Helper;
use App\kubiikslib\AuthTrait;
use App\kubiikslib\ImageTrait;
use JWTAuth;
use App;

use Intervention\Image\ImageManager;

class UserController extends Controller
{
    use AuthTrait;
    use ImageTrait;
    //
    public function index() {
        //return User::all();
        return response()->json([
            'message' => TrialAbstract::test()
        ],200);        
    }

    public function imageTest() {
        $avatar = 'data:image/png;base64,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';
        $this->saveImage($avatar, 'toto.jpg');

    }
    
    public function test(Request $request) {
        $lang = 'en'; //this value should dynamic
        app::setLocale($lang); 
        return response()->json(["string"=> __('auth.test', ['param'=>'sergi'])],200);
    }


 
}
