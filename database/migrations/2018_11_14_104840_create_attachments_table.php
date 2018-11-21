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
            $table->string('default'); //Default type of attachment if any: avatar, product,...
            $table->string('url');
            $table->string('alt_text')->default("Alt text");
            $table->string('title')->nullable(true)->default(null);
            $table->string('mime_type');     //Type if is document or image...
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_extension');
            $table->integer('file_size')->unsigned();
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
