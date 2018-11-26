<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{


    public $guarded = []; //Allow all fields as fillable

    //Return the attachments if any
    public function attachments() {
        return $this->morphMany(Attachment::class,'attachable');
    }

   //User delete from db
   public function delete()
   {
       //Remove the attachments
       foreach ($this->attachments()->get() as $attachment) {
           $attachment->remove();
       }
       //Parent delete
       parent::delete();
   }

}
