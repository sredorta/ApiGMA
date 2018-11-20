<?php
namespace App\kubiikslib; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class Helper
{

   //Generates a random str
   public static function generateRandomStr(
       $length,
       $keyspace = '01234567890123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
       ) {
        $str = '';  
        $max = mb_strlen($keyspace, '8bit') - 1;
        if ($max < 1) {
            throw new Exception('$keyspace must be at least two characters long');
        }
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
   }

   //Generates a random password making sure that the random string comtains lower,upper and number
   public static function generatePassword(
    $length,
    $keyspace = '01234567890123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ) {      
        $str = '';
        while (!(preg_match('/[A-Z]/', $str) && preg_match('/[a-z]/', $str) && preg_match('/[0-9]/', $str))) {
            $str = Helper::generateRandomStr($length, $keyspace);
        }
        return $str;
    }


   public static function test() {
       return "This is a test";
   }
}