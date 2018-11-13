<?php
namespace App\kubiikslib; 
use App\kubiikslib\Helper;
use Illuminate\Support\Str;

trait AuthTrait {
 
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

}