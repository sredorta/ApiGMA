<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->increments('id');            
            $table->string('attachable_type');
            $table->integer('attachable_id')->unsigned();
            $table->string('function'); //Function of the attachment: avatar, gallery, document...
            $table->string('filepath');     //Relative path to the file in case of image is path + filename + orig.jpeg or 50.jpeg... 
            $table->string('name');     //Random file name
            $table->string('type');     //Type if is document or image...
            $table->string('extension');                  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attachments');
    }
}
