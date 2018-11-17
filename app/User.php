<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class User extends Model
{
    use Notifiable;

    public function koko() {
 /*       $result = new static();
        $objJson = json_decode($json);
        $class = new \ReflectionClass($result);
        $publicProps = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($publicProps as $prop) {
             $propName = $prop->name;
             if (isset($objJson->$propName)) {
                 $prop->setValue($result, $objJson->$propName);
             }
             else {
                 $prop->setValue($result, null);
             }
        }
        return $result;  */     
        return true; 
    }


    public function test() {
        
    }

    //Return the attachments if any
    public function attachments() {
        return $this->morphMany(Attachment::class,'attachable');
    }

    //Return the accounts of the user
    public function accounts() {
        return $this->hasMany('App\Account');
    }
    
    //Return the roles of the profile
    public function roles() {
        return $this->belongsToMany('App\Role');
    }

    //Return the notifications of the user
    public function notifications() {
        return $this->hasMany('App\Notification');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstName','lastName', 'email', 'mobile','isEmailValidated','emailValidationKey'
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
