<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Role extends Model
{
    protected $fillable = [
        'name','isUnique','description'
    ];   

    //Define Role as a role to many profiles
    public function users() {
        return $this->belongsToMany('App\User');
    }  


    public function delete() {
        DB::table('role_user')->where('role_id', $this->id)->delete();
        parent::delete();
    }



}
