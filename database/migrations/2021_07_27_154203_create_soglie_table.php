<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoglieTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soglie', function (Blueprint $table) {
            $table->id();
            $table->float('rischio_elevato');
            $table->float('situazione_critica');
            $table->float('buono');
            $table->float('ottimo');
            $table->string('indice_riferimento');
            $table->string('tipo_azienda')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('soglie');
    }
}
