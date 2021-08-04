<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesCollections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('image_collections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('model');
            $table->string('uniq_path')->nullable();
            $table->string('name')->nullable();
            $table->string('fixed_url')->nullable();
            $table->string('collection')->nullable();
            $table->string('conversion')->nullable();
            $table->unsignedInteger('fk_id');
            $table->index('model');
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
        Schema::dropIfExists('image_collections');
    }
}
