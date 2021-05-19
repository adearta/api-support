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
            $table->bigIncrements('id');
            $table->string('zoom_link');
            $table->string('event_name');
            $table->timestamp('event_date');
            $table->timestamp('event_time');
            $table->string('event_picture');
            $table->string('school_name');
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
