<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsRoomchat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_support_models_roomchat', function (Blueprint $table) {
            $table->id();
            $table->integer('school_id')->nullable();
            $table->integer('student_id')->nullable();
            $table->bigInteger("creator_id")->nullable();
            $table->bigInteger("modifier_id")->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();
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
        Schema::dropIfExists('career_support_models_roomchat');
    }
}
