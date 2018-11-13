<?php
namespace App\kubiikslib; 
use App\kubiikslib\Helper;
use Illuminate\Support\Str;
use App\User;
use App\Account;

trait AuthTrait {
 protected $AuthTrait_user;     //Contains the user to avoid multiple searches
 protected $AuthTrait_accounts; //Contains the accounts to avoid multiple searches

 //Sets current user in order to avoid multiple query
 public function setCurrentUser($id) {
  $this->AuthTrait_user = User::find($id);
  $this->AuthTrait_accounts = $this->AuthTrait_user->accounts()->get();
 }

 //Generates a random password
 public function generatePassword() {
   return Helper::generatePassword(10);
 }

 //Returns token life depending on keepconnected
 public function getTokenLife($keep = false) {
    if (!$keep) 
      return 120; //120 minuntes if we are not keeping connection
    return 43200;           //30 days if we keep connected
 }

 //Checks if there are multiple accounts for the user with current access
 public function checkMultipleAccounts($access = null) {
   if ($access == null && $this->AuthTrait_accounts->count()>1) 
     return $this->AuthTrait_accounts->pluck('access');
   else 
      return null;  
 }

 //Get the selected account
 public function getAccount($access = null) {
  return response()->json([
    'response' => 'multiple_access',
    'message' => 'test',
  ],200);  
  if ($access != null)  
    return $this->AuthTrait_accounts->where('access', $access)->first();
  else  
    return $this->AuthTrait_accounts->first(); 
 }

}