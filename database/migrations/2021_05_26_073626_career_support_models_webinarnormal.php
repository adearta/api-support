<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsWebinarnormal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_support_models_webinarnormal', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->date('event_date');
            $table->string('event_picture');
            $table->string('event_link');
            $table->time('event_start');
            $table->time('event_end');
            $table->integer('price')->nullable();

            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();
            $table->boolean('is_certificate')->default(false);
            $table->string('certificate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('areer_support_models_webinarnormal');
    }
}
