<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelParticipants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //references webinar
        Schema::create('career_support_model_partcipants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('webinar_id')->unsigned();
            $table->bigInteger('school_id')->unsigned();
            $table->string('name')->unsigned()->nullable();
            $table->timestamp('date')->unsigned()->nullable();
            $table->timestamp('time')->unsigned()->nullable();
            $table->string('picture')->unsigned()->nullable();
            $table->string('link')->unsigned()->nullable();
            $table->string('school')->unsigned()->nullable();
            // $table->boolean('is_join');

            $table->foreign('webinar_id')->references('id')->on('career_support_models_webinar');
            $table->foreign('school_id')->references('id')->on('career_support_models_school');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('career_support_model_partcipants');
    }
}
