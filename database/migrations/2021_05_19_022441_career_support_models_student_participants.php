<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsStudentParticipants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('career_support_models_student_participants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->unsigned();
            $table->bigInteger('webinar_id')->unsigned();
            //
            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            //
            $table->boolean('is_deleted');
            $table->timestamp('created');
            $table->timestamp('modified');
            //i want to add webinar attributes

            // i want to add student attributes

            //this to relational in database
            $table->foreign('student_id')->references('id')->on('career_support_models_student');
            $table->foreign('webinar_id')->references('id')->on('career_support_models_webinar_akbar');
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
        Schema::dropIfExists('career_support_models_student_participants');
    }
}
