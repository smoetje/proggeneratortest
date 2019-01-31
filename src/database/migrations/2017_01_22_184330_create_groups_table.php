<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('groepsnaam', 100);
            $table->string('groepscode', 100)->unique();   // letters & numbers only, no spaces, no caps
            $table->string('genre')->nullable();
            $table->string('subgenre')->nullable();
            $table->string('omschrijvingkort', 4096)->nullable();
            $table->string('omschrijvinglang', 4096)->nullable();
            $table->string('url', 4096)->nullable();
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
        Schema::dropIfExists('groups');
    }
}
