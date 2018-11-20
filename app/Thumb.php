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

    //From a valid attachment id we create all the thumbs and corresponding files
    public static function add($attachment_id) {
        $attachment = Attachment::find($attachment_id);
        $manager = new ImageManager(array('driver' => 'gd'));

        //Get the original image
        $image = Storage::disk('public')->get($attachment->file_path . $attachment->file_name . "." . $attachment->file_extension);
        $imageOrig = $manager->make($image);   

        foreach (Config::get('constants.THUMBS') as $size_text => $size_value) {

            //Resize and save new image
            $image = Thumb::resizeImage($imageOrig,$size_value);
            $stream = $image->stream('jpg',90);
            $path = $attachment->file_path . $attachment->file_name . "/". $size_text . "/" . $image->width() . "x" . $image->height() . "." . $attachment->file_extension;
            Storage::disk('public')->put($path, $stream);

            Thumb::create([  
                'attachment_id' => $attachment_id,
                'url' => env('APP_ENV') == 'testing' ? env('APP_URL'). "/tests/storage/" . $path : env('APP_URL'). "/storage/" . $path,
                'size' => $size_text,
                'width' =>$image->width(),
                'height' =>$image->height(),
                'file_size' => Storage::disk('public')->size($path),
            ]);
        }
    }

}
