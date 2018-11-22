<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessageUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('message_id')->unsigned()->nullable();
            $table->foreign('message_id')->references('id')
                  ->on('messages')->onDelete('cascade');

            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')
                  ->on('users')->onDelete('cascade');   

            $table->integer('from_user_id')->unsigned()->nullable();
                  $table->foreign('from_user_id')->references('id')
                        ->on('users')->onDelete('set null');   
            $table->string("from_user_first")->nullable();
            $table->string("from_user_last")->nullable();                           
            $table->boolean('isRead')->default(false);                                          
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_user');
    }
}
