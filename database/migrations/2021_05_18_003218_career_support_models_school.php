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

            $table->id();
            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            $table->string('school_name');
            $table->string('school_email');
            $table->boolean('is_deleted')->dafault(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();

            // is_deleted, created, modified, creator_id sama modifier_id
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
