<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class User extends Model
{
    use Notifiable;

    //Return the attachments if any
    public function attachments() {
        return $this->morphMany(Attachment::class,'attachable');
    }

    //Return the images if any
    public function images() {
        return $this->morphMany(Image::class,'imageable');
    }

    //Return the accounts of the user
    public function accounts() {
        return $this->hasMany('App\Account');
    }
    
    //Return the roles of the user
    public function roles() {
        return $this->belongsToMany('App\Role');
    }

    //Return the notifications of the user
    public function notifications() {
        return $this->hasMany('App\Notification');
    }

    //Return the messages of the user
    public function messages() {
        return $this->belongsToMany('App\Message')->withPivot('from_user_id','from_user_first','from_user_last', 'isRead');    //$user->messages() : get the messages to us
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstName','lastName', 'email', 'mobile','isEmailValidated','emailValidationKey','language'
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'emailValidationKey',
    ];

    //User delete from db
    public function delete()
    {
        // delete all related roles (needs to be done with all related tables)
        $this->roles()->detach();
        //$this->groups()->detach();
        $this->notifications()->delete();
        $this->attachments()->delete();
        $this->accounts()->delete();

        //Parent delete
        parent::delete();
    }
}
