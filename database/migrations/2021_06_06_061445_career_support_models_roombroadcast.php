<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsRoombroadcast extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_support_models_roombroadcast', function (Blueprint $table) {
            $table->id();
            $table->integer('school_id')->nullable();
            $table->integer('broadcast_type')->default(0); //1 -> all,2 -> by year,3 -> specific student
            $table->integer('year')->nullable(); // use this coloumn when the broadcast_type is 2
            $table->bigInteger("creator_id")->nullable();
            $table->bigInteger("modifier_id")->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('career_support_models_roombroadcast');
    }
}
