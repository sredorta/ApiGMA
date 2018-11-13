<?php

namespace App;

use Illuminate\Notifications\Notifiable;

class User
{
    use Notifiable;


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
