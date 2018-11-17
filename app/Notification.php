<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'text', 'isRead'
    ];
    
    //Define Role as a user to Many
    public function notification() {
        return $this->belongsTo('App\User');
    }    
}
