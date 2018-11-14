<?php
namespace App\kubiikslib; 

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\kubiikslib\Helper;


trait EmailTrait {

    public function sendEmail($to, $subject, $data) {
        Mail::send('emails.generic',$data, function($message) use ($subject, $to)
        {
            $message->from(Config::get('constants.EMAIL_FROM_ADDRESS'), Config::get('constants.EMAIL_FROM_NAME'));
            $message->replyTo(Config::get('constants.EMAIL_NOREPLY'));
            $message->to($to);
            $message->subject($subject);
        });   
    }

}