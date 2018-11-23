<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'subject','text','isRead'
    ];   

    //Define Role as a role to many users
    public function users() {
        return $this->belongsToMany('App\User')->withPivot('from_user_id','from_user_first','from_user_last','isRead');//, 'message_user','to_user_id'); //$message->users() : Get the originator of the message
    }  

    public function delete() {
        parent::delete();
    }
}
