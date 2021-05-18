<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCareerSupportModelsSchooljoinwebinarBkk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_support_models_schooljoinwebinar_bkk', function (Blueprint $table) {
            $table->id();

            // $table->bigInteger("webinar_id")->unsigned(); //
            // $table->bigInteger("school_id")->unsigned();

            // $table->bigInteger("creator_id")->unsigned()->nullable();
            // $table->bigInteger("modifier_id")->unsigned()->nullable();
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();
            $table->boolean('is_deleted')->default(false);

            // $table->foreign('webinar_id')->references('id')->on('career_support_models_schoolwebinar_bkk');
            // $table->foreign('school_id')->references('id')->on('career_support_models_school');
            // $table->foreign('creator_id')->references('id')->on('career_support_models_user');
            // $table->foreign('modifier_id')->references('id')->on('career_support_models_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('career_support_models_schooljoinwebinar_bkk');
    }
}
