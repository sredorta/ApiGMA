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
    public function saveBase64Image($base64, $filename) {
        // create an image manager instance with favored driver
        $manager = new ImageManager(array('driver' => 'gd'));
        $filename = $filename . "." .  $this->getBase64Extension($base64);
        $base64 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        // to finally create image instances
        $image = $manager->make($base64);   
        $width = $image->width();
        $height = $image->height();

        $stream = $image->stream();
        Storage::disk('images')->put($filename, $stream);
        //Now create the different sizes
        $image = $this->resizeImage($image,500);
        $stream = $image->stream();
        Storage::disk('images')->put($filename ."_500", $stream);
        //$image->save('./' . $filename ."_500"); 
        $image = $this->resizeImage($image,200);
        $stream = $image->stream();
        Storage::disk('images')->put($filename ."_200", $stream);
        //$image->save('./' . $filename ."_200");  
        $image = $this->resizeImage($image,100);
        $stream = $image->stream();
        Storage::disk('images')->put($filename ."_100", $stream);
        //$image->save('./' . $filename ."_100");                 
        $image = $this->resizeImage($image,50);
        $stream = $image->stream();
        Storage::disk('images')->put($filename ."_50", $stream);
        //$image->save('./' . $filename ."_50"); 
    }

}