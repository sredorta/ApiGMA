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
        return $this->belongsToMany('App\User');
    }  


    public function delete() {
        //DB::table('role_user')->where('role_id', $this->id)->delete();
        parent::delete();
    }
}
