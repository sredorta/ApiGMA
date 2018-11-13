<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class User extends Model
{
    use Notifiable;

    //Return the accounts of the user
    public function accounts() {
        return $this->hasMany('App\Account');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstName','lastName', 'email', 'mobile','avatar','isEmailValidated','emailValidationKey'
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'emailValidationKey',
    ];

    public function kk() {
    echo 'kk';
    }
}
