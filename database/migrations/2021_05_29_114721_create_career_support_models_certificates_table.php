<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCareerSupportModelsCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_support_models_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate');
            $table->integer('participant_id');
            // $table->integer('student_id');
            // $table->timestamps();
            $table->foreign('participant_id')->references('id')->on('career_support_models_normal_studentparticipants');
            // $table->foreign('webinar_id')->references('webinar_id')->on('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('career_support_models_certificates');
    }
}
