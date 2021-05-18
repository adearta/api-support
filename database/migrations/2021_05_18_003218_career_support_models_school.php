<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use League\CommonMark\Extension\Table\Table;

class CareerSupportModelsSchool extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('career_support_models_school', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->string('school_name');
            // $table->bigInteger('webinar_id')->unsigned();
            // $table->string('name')->unsigned()->nullable();
            // $table->timestamp('date')->unsigned()->nullable();
            // $table->timestamp('time')->unsigned()->nullable();
            // $table->string('picture')->unsigned()->nullable();
            // $table->string('link')->unsigned()->nullable();
            // $table->bigInteger('creator_id')->unsigned();//ini nantinya admin sekolah
            // $table->bigInteger('modifier_id')->unsigned();
            // $table->boolean('is_deleted');
            // $table->boolean('is_created');
            // $table->timestamp('modified');
            //modified, creator_id sama modifier_id

            // $table->foreign('webinar_id')->references('id')->on('career_support_models_webinar');
            // $table->foreign('name')->references('event_name')->on('career_support_models_webinar');
            // $table->foreign('date')->references('event_date')->on('career_support_models_webinar');
            // $table->foreign('time')->references('event_time')->on('career_support_models_webinar');
            // $table->foreign('picture')->references('event_picture')->on('career_support_models_webinar');
            // $table->foreign('link')->references('link_zoom')->on('career_support_models_webinar');
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
        Schema::dropIfExists('career_support_models_school');
    }
}
