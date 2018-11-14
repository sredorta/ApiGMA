<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\kubiikslib\Helper;
use App\kubiikslib\ImageTrait;
use Illuminate\Support\Facades\Config;

class Attachment extends Model
{
    use ImageTrait;
    public $guarded = []; //Allow all fields as fillable

    public function attachable() {
        return $this->morphTo();
    }



    public function add($id, $type, $function, $image) {     
        //Validate that class is attachable and that we get a subject
        if (!(class_exists($type) && method_exists($type, 'attachments'))) {
            return null;
        }
        $subject = call_user_func($type . '::find', $id);
        if (!$subject) {
            return null;
        }
        //Validation has passed so generate a random file name and save the image with the name

        $fileName = Helper::generateRandomStr(20);
        $attached = $subject->attachments()->create(['function'=>$function, 'name'=> $fileName, 'type'=> $this->getBase64Type($image), 'extension'=> $this->getBase64Extension($image) ]);
        $this->saveBase64Image($image, $fileName);
        return $attached;
    }



}
