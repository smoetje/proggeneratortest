<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fotos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url', 4096)->nullable();
            $table->string('name')->nullable();
            $table->integer('height')->nullable();  // als GLIDE goed functioneert, zal die height niet meer nodig zijn...?
            $table->string('path')->nullable();
            $table->string('extension')->nullable();
            $table->string('groepscode_fk')->nullable();
            $table->integer('group_id')->nullable();   // New! change controller to add group id...
            $table->unsignedInteger('size')->nullable(); // 1 to 3
            $table->tinyInteger('sheet_index')->nullable(); // 1 to 3//
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
        Schema::dropIfExists('fotos');
    }
}
