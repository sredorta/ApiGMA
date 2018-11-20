<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\kubiikslib\Helper;
use App\kubiikslib\ImageTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use finfo;
use App\Thumb;

class Attachment extends Model
{

    public $guarded = []; //Allow all fields as fillable


/*    protected $unguard = [
        'file_path','file_name','file_extension'
    ];*/

    public function attachable() {
        return $this->morphTo();
    }
    //Return the thumbs of the attachable
    public function thumbs() {
        return $this->hasMany('App\Thumb');
    }

    //In case input is not a base64 we load the default
    private function getDefaultBase64($default) {
        switch ($default) {
            case "avatar":
                return Storage::disk('public')->get('defaults/user-default.jpg');
                break;
            default:
                return null;
        }
    }

    //Expects base64 file and returns the data only
    private function getFileFromBase64($base64) {
        if (!preg_match('/^data:.*;base64,/',  $base64) ) return null;
        return base64_decode(preg_replace('/^data:.*;base64,/', '', $base64));
    }

    //Expects a decoded base64 file and returns the mime type
    private function getMimeType($buffer) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($buffer);
    }

    //Gets media type
    private function getMediaType($buffer) {
        $mime = $this->getMimeType($buffer);
        return preg_replace('/\/.*/', '', $mime);
    }

    //Gets media type
    private function getMediaExtension($buffer) {
        $mime = $this->getMimeType($buffer);
        return preg_replace('/.*\//', '', $mime);
    }

    private function getPath($id, $type , $root) {
        $type = preg_replace('/^App\\\/', '', $type);
        return $root . '/' . $type . '/' . $id . '/';
    }


    //Add an attachable register and copy the associated data
    public function add($id, $type, $default, $root, $alt_text, $filedata) {     
        //Validate that class is attachable and that we get a subject
        if (!(class_exists($type) && method_exists($type, 'attachments'))) {
            return null;
        }
        $subject = call_user_func($type . '::find', $id);
        if (!$subject) {
            return null;
        }

        $file = $this->getFileFromBase64($filedata);
        //Get the default image depending on application
        if ($file === null) {
            $file = $this->getDefaultBase64($default);
        }
        if ($file === null) {
            return null;    //The file could not be processed and there was no default
        }

        //Now store the file in the right place
        $file_name = Helper::generateRandomStr(20);
        $file_extension = $this->getMediaExtension($file);
        $fileName = $file_name . "." . $file_extension;

        Storage::disk('public')->put($this->getPath($id, $type, $root) . $fileName, $file);

        //Add the record in the table
        $attached = $subject->attachments()->create([ 
            'default' => $default,
            'file_path' => $this->getPath($id, $type, $root),
            'file_name' => $file_name,
            'file_extension' => $file_extension,
            'file_size' => Storage::disk('public')->size($this->getPath($id, $type, $root) . $fileName, $file),
            'url'=> env('APP_ENV') == 'testing' ? env('APP_URL'). "/tests/storage/" . $this->getPath($id, $type, $root) . $fileName : env('APP_URL'). "/storage/" . $this->getPath($id, $type, $root) . $fileName, 
            'alt_text' => $alt_text,
            'mime_type'=> $this->getMimeType($file)
            ]);

        //Now if media_type is image then we create all Thumbnails
        if ($this->getMediaType($file) === 'image') {
            Thumb::add($attached->id);
        }
        return $attached->with('thumbs');
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
