<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name','isUnique','description'
    ];   

    //Define Role as a role to many profiles
    public function users() {
        return $this->belongsToMany('App\User');
    }  
}
