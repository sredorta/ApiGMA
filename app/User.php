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
        echo "This is a test";
    }

    //Return the attachments if any
    public function attachments() {
        return $this->morphMany(Attachment::class,'attachable');
    }

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


}
