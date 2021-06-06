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
            $table->integer('participant_id')->unsigned()->nullable();
            $table->integer('participant_akbar_id')->unsigned()->nullable();
            $table->integer('webinar_id')->unsigned()->nullable();
            $table->integer('webinar_akbar_id')->unsigned()->nullable();
            $table->string('file_name');
            $table->bigInteger("creator_id")->nullable();
            $table->bigInteger("modifier_id")->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();

            $table->foreign('webinar_id')->references('id')->on('career_support_models_webinarnormal')->onDelete('cascade');
            $table->foreign('participant_id')->references('id')->on('career_support_models_normal_studentparticipants')->onDelete('cascade');
            $table->foreign('participant_akbar_id')->references('id')->on('career_support_models_studentparticipantakbar')->onDelete('cascade');
            $table->foreign('webinar_akbar_id')->references('id')->on('career_support_models_webinarakbar')->onDelete('cascade');
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
