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
        //
        Schema::create('career_support_models_student', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('school_id')->nullable();
            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            $table->string('name');
            $table->string('batch')->nullable();
            $table->string('class')->nullable();
            $table->string('nim')->nullable();
            // $table->string('year')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();

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
        Schema::dropIfExists('career_support_models_student');
    }
}
