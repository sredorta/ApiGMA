<?php
namespace App\kubiikslib; 

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use App\kubiikslib\Helper;


trait ImageTrait {

    //From a base64 returns the type (image...)
    public function getBase64Type($base64) {
        $result =  preg_replace('/;.*/', '', $base64);
        $result = preg_replace('/\/.*/', '', $result);
        $result = preg_replace('/data:/', '', $result);

        return $result;

    }

    //From a base64 returns the extension jpg/png...
    public function getBase64Extension($base64) {
        $result =  preg_replace('/;.*/', '', $base64);
        $result = preg_replace('/.*\//', '', $result);
        return $result;  
    }

    //Resize image keeping aspect ratio !
    public function resizeImage($image, $size) {
        if ($image->width()>=$image->height()) {
            $image->resize($size,null,function($constraint) {
                $constraint->aspectRatio();
            }); 
        } else {
            $image->resize(null,$size,function($constraint) {
                $constraint->aspectRatio();
            });             
        }
        return $image;
    }

    //Takes input image and saves it with different sizes
    public function saveBase64Image($base64, $filename, $path) {
        // create an image manager instance with favored driver
        $manager = new ImageManager(array('driver' => 'gd'));
        $base64 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        // to finally create image instances
        $image = $manager->make($base64);   
        $width = $image->width();
        $height = $image->height();

        $stream = $image->stream('jpg',90);
        Storage::disk('public')->put($path . $filename . "/orig.jpeg", $stream);
        //Now create the different sizes
        $image = $this->resizeImage($image,500);
        $stream = $image->stream('jpg',90);
        Storage::disk('public')->put($path . $filename . "/500.jpeg", $stream);

        $image = $this->resizeImage($image,200);
        $stream = $image->stream('jpg',90);
        Storage::disk('public')->put($path . $filename . "/200.jpeg", $stream);

        $image = $this->resizeImage($image,100);
        $stream = $image->stream('jpg',90);
        Storage::disk('public')->put($path . $filename . "/100.jpeg", $stream);

        $image = $this->resizeImage($image,50);
        $stream = $image->stream('jpg',90);
        Storage::disk('public')->put($path . $filename . "/50.jpeg", $stream);

        $image = $this->resizeImage($image,30);
        $stream = $image->stream('jpg',90);
        Storage::disk('public')->put($path . $filename . "/30.jpeg", $stream);
    }

}