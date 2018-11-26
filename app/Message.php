<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'subject','text','isRead', 'to_user_list', 'to_group_list', 'from_first', 'from_last', 'from_id'
    ];   

    //A message has one user
    public function user() {
        return $this->belongsTo('App\User');
    }

    public function delete() {
        parent::delete();
    }
}
