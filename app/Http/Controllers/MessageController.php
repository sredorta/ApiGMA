<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function send(Request $request) {
        echo "WE ARE IN SEND !";
    }

}
