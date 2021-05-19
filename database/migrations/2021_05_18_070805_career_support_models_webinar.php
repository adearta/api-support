<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsWebinar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('career_support_models_webinar_akbar', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            $table->string('zoom_link');
            $table->string('event_name');
            $table->date('event_date');
            $table->timestamp('event_time');
            $table->string('event_picture');
            $table->boolean('is_deleted');
            $table->timestamp('created');
            $table->timestamp('modified');
            //event(name, date, time, picture),school_name 
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
        Schema::dropIfExists('career_support_models_webinar_akbar');
    }
}
