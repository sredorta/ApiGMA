<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;


class Thumb extends Model
{
    //
    public $guarded = []; //Allow all fields as fillable

    public function attachment() {
        return $this->belongsTo('App\Attachment');
    }

    //Resize image keeping aspect ratio !
    public static function resizeImage($image, $size) {
        $mySize = $image->width()>=$image->height()?$image->width():$image->height();
        if ($mySize>= $size) {
            if ($image->width()>=$image->height()) {
                $image->resize($size,null,function($constraint) {
                    $constraint->aspectRatio();
                }); 
            } else {
                $image->resize(null,$size,function($constraint) {
                    $constraint->aspectRatio();
                });             
            }
        }
        return $image;
    }
    //Crops image for thumbnails
    public static function cropImage($image) {
        $width = $image->width();
        $height = $image->height();
        $startx = $starty = 0;
        if ($width>$height) {
            $size = $width;
        } else {
            $size = $height;
        }
        $image = $image->fit($size,$size);
        return $image;
    }

    //From a valid attachment id we create all the thumbs and corresponding files
    public static function add($attachment_id) {
        $attachment = Attachment::find($attachment_id);
        $manager = new ImageManager(array('driver' => 'gd'));

        //Get the original image
        $image = Storage::disk('public')->get($attachment->getPath());
        $imageOrig = $manager->make($image);   

        foreach (Config::get('constants.THUMBS') as $size_text => $size_value) {
            //Resize and save new image
            if (strpos($size_text, "thumbnail") !== false) {
                $image = Thumb::cropImage($image);
            }
            $image = Thumb::resizeImage($imageOrig,$size_value);

            $stream = $image->stream('jpg',90);
            $path = $attachment->getRelativePath() . $size_text . "/" . $attachment->file_name;
            $url = Storage::disk('public')->url($path);
            Storage::disk('public')->put($path, $stream);
            Thumb::create([  
                'attachment_id' => $attachment_id,
                'url' => $url,
                'size' => $size_text,
                'width' =>$image->width(),
                'height' =>$image->height(),
                'file_size' => Storage::disk('public')->size($path),
            ]);
        }
    }

    private function getPath() {
        return str_replace(Storage::disk('public')->url(''), '', $this->url);
    }
    
    //Remove the record and the associated file
    public function remove() {
        Storage::disk('public')->delete($this->getPath());
        $this->delete();    //Remove db record
    }
    

}
