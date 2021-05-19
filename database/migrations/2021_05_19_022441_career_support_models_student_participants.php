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
            $table->bigIncrements('id');
            $table->bigInteger('student_id')->unsigned();
            $table->bigInteger('webinar_id')->unsigned();
            //i want to add webinar attributes
            $table->string('name')->unsigned()->nullable();
            $table->timestamp('date')->unsigned()->nullable();
            $table->timestamp('time')->unsigned()->nullable();
            $table->string('picture')->unsigned()->nullable();
            $table->string('link')->unsigned()->nullable();
            // i want to add student attributes
            $table->string('student_name')->unsigned()->nullable();
            $table->string('student_nim')->unsigned()->nullable();
            $table->string('student_batch')->unsigned()->nullable();
            $table->string('student_class')->unsigned()->nullable();
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
