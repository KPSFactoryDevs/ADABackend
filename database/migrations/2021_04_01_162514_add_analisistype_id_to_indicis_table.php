<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnalisistypeIdToIndicisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('indicis', function (Blueprint $table) {
            $table->biginteger('analisistype_id')->nullable()->unsigned(); 
            $table->foreign('analisistype_id')->references('id')->on('analisistype');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('indicis', function (Blueprint $table) {
            //
        });
    }
}
