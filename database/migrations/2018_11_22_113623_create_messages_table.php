<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');  
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')
                  ->on('users')->onDelete('cascade');  
            $table->integer("from_id")->unsigned()->nullable();      
            $table->string("from_first")->nullable();
            $table->string("from_last")->nullable();    
            $table->string("to_user_list",100)->nullable();
            $table->string("to_group_list",100)->nullable();             
            $table->boolean('isRead')->default(false);  
            $table->string('subject',100);
            $table->string('text',1000); 
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
        Schema::dropIfExists('messages');
    }
}
