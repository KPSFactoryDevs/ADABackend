<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('account_id');
            $table->integer('anno');
            $table->string('mese');
            $table->string('nome_banca');
            $table->string('sezione');
            $table->string('categoria');
            $table->string('accordato');
            $table->string('accordato_operativo');
            $table->string('utilizzato');
            $table->string('durata_residua');
            $table->string('durata_originaria');
            $table->string('localizzazione');
            $table->string('divisa');
            $table->string('tipo_garanzia');
            $table->string('stato_rapporto');
            $table->string('tipo_attivita');
            $table->string('ruolo_affidato');
            $table->string('import_export');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crs');
    }
}
