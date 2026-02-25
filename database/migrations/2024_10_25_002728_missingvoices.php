<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissingVoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('missingVoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documentId');
            $table->string('voiceFullName')->nullable();
            $table->string('voiceLabel')->nullable();
            $table->text('voiceValue')->nullable();
            $table->string('period')->nullable();
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
        Schema::dropIfExists('missingVoices');
    }
}
