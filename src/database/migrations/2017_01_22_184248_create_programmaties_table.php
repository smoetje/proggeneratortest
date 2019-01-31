<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProgrammatiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programmaties', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('dagnr');
            $table->date('datum')->nullable();
            $table->time('uur')->nullable();
            $table->smallInteger('chronologie')->nullable(); //Later bruikbaar om programmatie chronologisch te sorteren
            $table->integer('locatie_id')->nullable();
            $table->string('groepscode_fk');
            $table->integer('group_id')->nullable();  // New! change controller to add group id...
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
        Schema::dropIfExists('programmaties');
    }
}
