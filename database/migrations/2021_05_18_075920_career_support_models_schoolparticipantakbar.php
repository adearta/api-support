<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsSchoolparticipantakbar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //references webinar
        Schema::create('career_support_models_schoolparticipantakbar', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('webinar_id')->unsigned();
            $table->bigInteger('school_id')->unsigned();
            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();
            $table->date('schedule')->nullable();
            $table->integer('status')->default(1);
            // 1 -> created
            // 2 -> rejected
            // 3 -> accepted
            // 4 -> submit the data of student

            $table->foreign('webinar_id')->references('id')->on('career_support_models_webinarakbar');
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
        Schema::dropIfExists('career_support_models_schoolparticipantakbar');
    }
}
