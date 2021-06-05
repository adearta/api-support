<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsNotificationwebinar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('career_support_models_notificationwebinar', function (Blueprint $table) {
            $table->id();
            $table->integer('school_id')->unsigned()->nullable();
            $table->integer('student_id')->unsigned()->nullable();
            $table->bigInteger('webinar_akbar_id')->unsigned()->nullable();
            $table->bigInteger('webinar_normal_id')->unsigned()->nullable();
            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            //translation
            $table->string('message_id');
            $table->string('message_en');
            $table->boolean('is_readed')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();

            $table->foreign('webinar_akbar_id')->references('id')->on('career_support_models_webinarakbar')->onDelete('cascade');
            $table->foreign('webinar_normal_id')->references('id')->on('career_support_models_webinarnormal')->onDelete('cascade');
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
        Schema::dropIfExists('career_support_models_notificationwebinar');
    }
}
