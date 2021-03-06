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
    
    //Return the groups of the user
    public function groups() {
        return $this->belongsToMany('App\Group');
    }    

    //Return the notifications of the user
    public function notifications() {
        return $this->hasMany('App\Notification');
    }

    //Return the messages of the user
    public function messages() {
        return $this->hasMany('App\Message');
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
        $this->groups()->detach();

        //Messages removal
        $this->messages()->delete();
        $this->notifications()->delete();

        //Remove the attachments
        foreach ($this->attachments()->get() as $attachment) {
            $attachment->remove();
        }

        $this->accounts()->delete();

        //Parent delete
        parent::delete();
    }
}
