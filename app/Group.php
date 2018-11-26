<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Group extends Model
{
    protected $fillable = [
        'name','description'
    ];   

    //Define Role as a role to many users
    public function users() {
        return $this->belongsToMany('App\User');
    }  


    public function delete() {
        DB::table('group_user')->where('group_id', $this->id)->delete();
        parent::delete();
    }
}
