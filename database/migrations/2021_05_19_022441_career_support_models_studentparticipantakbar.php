<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsStudentparticipantakbar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_support_models_studentparticipantakbar', function (Blueprint $table) {
            $table->id();
            $table->integer('school_id')->unsigned();
            $table->integer('student_id')->unsigned();
            $table->bigInteger('webinar_id')->unsigned();
            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();

            //this to relational in database
            // $table->foreign('student_id')->references('id')->on('career_support_models_student');
            // $table->foreign('school_id')->references('id')->on('career_support_models_school');
            $table->foreign('webinar_id')->references('id')->on('career_support_models_webinarakbar');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('career_support_models_studentparticipantakbar');
    }
}
