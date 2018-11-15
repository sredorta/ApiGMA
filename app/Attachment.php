<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\kubiikslib\Helper;
use App\kubiikslib\ImageTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use ImageTrait;
    public $guarded = []; //Allow all fields as fillable

    public function attachable() {
        return $this->morphTo();
    }

    //In case input is not a base64 we load the default
    private function getDefaultBase64($function) {
        switch ($function) {
            case "avatar":
                return "data:image/jpeg;base64," . base64_encode(Storage::disk('public')->get('defaults/user-default.jpg'));
                break;
            default:
                return "data:image/jpeg;base64," . base64_encode(Storage::disk('public')->get('defaults/user-default.jpg'));
        }
    }

    //Add an attachable register and copy the associated data
    public function add($id, $type, $function, $path, $filedata) {     
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
        //
        if (strlen($filedata) < 500) {
            $filedata = $this->getDefaultBase64($function);
        }
        $fileType = $this->getBase64Type($filedata);
        $fileExtension = $this->getBase64Extension($filedata);
        if ($fileType == "image") {
            $attached = $subject->attachments()->create(['filepath'=>$path, 'function' => $function,'name'=> $fileName, 'type'=> $fileType, 'extension'=> 'jpeg' ]);
            $this->saveBase64Image($filedata, $fileName, $path);
        } else {
            $attached = $subject->attachments()->create(['filepath'=>$path, 'function' => $function, 'name'=> $fileName ."." . $fileExtension , 'type'=> $fileType, 'extension'=> $fileExtension ]);
            $filedata = preg_replace('/^data:.*;base64,/', '', $filedata);
            Storage::disk('public')->put($path . $fileName . "." . $fileExtension, base64_decode($filedata));
        }
        return $attached;
    }

    //Delete an attachable register and delete the associated data
    public function delete() {
        //Remove all the related files
        if ($this->type == "image") {
            Storage::disk('public')->deleteDirectory($this->filepath);
        } else {
            //Need to remove the file here
        }
        //Remove the register from the database
        parent::delete();
    }



}
