<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsChat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_support_models_chat', function (Blueprint $table) {
            $table->id();
            $table->integer('room_chat_id')->unsigned();
            $table->string('chat')->nullable();
            $table->string('image')->nullable();
            $table->dateTime('send_time');
            $table->string('sender'); //student or school
            $table->boolean('is_readed')->default(false); //true -> has readed
            $table->bigInteger("creator_id")->nullable();
            $table->bigInteger("modifier_id")->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();
            $table->timestamps();

            $table->foreign('room_chat_id')->references('id')->on('career_support_models_roomchat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('career_support_models_chat');
    }
}
