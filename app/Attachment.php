<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\kubiikslib\Helper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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

    //Gets file extension
    private function getFileExtension($filename) {
        return substr($filename, strrpos($filename, '.')+1); 
    }

    //Get URL of file
    public function getUrl($default = false) {
        if (!$default) {
            return Storage::disk('public')->url('/uploads/' . $this->file_name);
        } else {
            return Storage::disk('public')->url('/defaults/' . $this->file_name);
        }
    }

    //Expects a decoded base64 file and returns the mime type
    private function getMimeType($default = false) {
        if (!$default){
            return Storage::disk('public')->mimeType('/uploads/' . $this->file_name);
        } else {
            return Storage::disk('public')->mimeType('/defaults/' . $this->file_name);
        }
    }

    //Store the file uploaded
    public function uploadFile(UploadedFile $file) {
        $file = $file->storePublicly('uploads', ['disk'=> 'public']);
        $this->file_name = basename($file);
        $this->file_extension = $this->getFileExtension($this->file_name);
        $this->file_size =  Storage::disk('public')->size('/uploads/' . $this->file_name);
        $this->url = $this->getUrl();
        $this->mime_type = $this->getMimeType();
        return $this;
    }

    //In case input file is null we get the default file (this works for avatar, product...)
    public function getDefault($default) {
        switch ($default) {
            case "avatar":
                $file = '/defaults/userdefault.jpg';
                break;
            default:
                $file = null;
        }
        if ($file !== null) {
            $this->file_name = basename($file);
            $this->file_extension = $this->getFileExtension($this->file_name);
            $this->file_size =  Storage::disk('public')->size('/defaults/' . $this->file_name);
            $this->url = $this->getUrl(true);
            $this->mime_type = $this->getMimeType(true);
        return $this;        
        } else {
            return null;
        }
    }

    public function getTargetFile($file, $default) {
        if($file !== null) {
            $this->uploadFile($file); //Automatically fills file_name, file_extension, file_size, url
        } else {
            $result = $this->getDefault($default); //Get default file and fills file_name...

            if ($result == null) {
//                return response()->json(['response'=>'error', 'message'=>__('attachment.default', ['default' => $default])], 400);
                return ['response'=>'error', 'message'=>__('attachment.default', ['default' => $default])];
            }      
        }
    }

    //Returns the file path from the public disk
    public function getPath() {
        return str_replace(Storage::disk('public')->url(''), '', $this->url);
    }

    public function getRelativePath() {
        $str = str_replace(Storage::disk('public')->url(''), '', $this->url);
        $str = str_replace($this->file_name, '', $str);
        return $str;
    }   

    //Create thumbnails if is image and if not defaults
    private function createThumbs() {
        if (strpos($this->mime_type, 'image') !== false) {
            if (strpos($this->url, '/defaults/') === false) {
                Thumb::add($this->attachable_id);
            }
        }
    }

    //Save the register and create the thumbnails if required
    public function save(array $options = []) {
        parent::save($options);
        $this->createThumbs();
    }

    //Delete an attachable register and delete the associated data
    public function remove() {
        //Check if there are thumbs and delete files and db
        foreach ($this->thumbs()->get() as $thumb) {
            $thumb->remove();
        }
        //Delete the attachable itself only if is not default
        if (strpos($this->url, '/defaults/') === false) {
            Storage::disk('public')->delete($this->getPath());
        }
        $this->delete();    //Remove db record
    }



}
