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
    use AuthTrait;  //Use Auth trait

    public function test() {
        /*   return response()->json(Account::all(),200); 
           $key = $this->generatePassword();
           echo $key;
           $time = $this->getTokenLife(false);
           echo $time;*/
           //$key = $this->generatePassword();
           //echo $key;
           //echo User::find(1)->accounts();
           /*return response()->json([
               'response' => 'success',
               'message' => User::find(1)->accounts()->get()
           ],200);         */   
           $this->setCurrentUser(2);
   
           /*$accounts = $this->checkMultipleAccounts();
           if ($accounts !== null) {
               return response()->json([
                   'response' => 'multiple_access',
                   'message' => $accounts,
               ],200);             
           }*/
           echo $this->getAccount("admin");

   /*        return;
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
                   'myret' => Helper::generatePassword(5)
               ],401);              
           }*/
       }   

}
