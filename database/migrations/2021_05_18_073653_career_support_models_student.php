<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsStudent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //references participans
        Schema::create('caree_support_models_student', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('participants_id');
            $table->string('name');
            $table->timestamp('date');
            $table->timestamp('time');
            $table->string('picture');
            $table->string('link');

            $table->foreign('participants_id')->references('id')->on('career_school_models_participants');
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
    }
}
