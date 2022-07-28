<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBasicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('basic', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->bigInteger('bilancio_id');
            $table->string('DSCRD');
            $table->string('DSCRDate');
            $table->bigInteger('DSCRdispLiquida');
            $table->bigInteger('entrataDSCRCFmese1');
            $table->bigInteger('entrataDSCRCFmese2');
            $table->bigInteger('entrataDSCRCFmese3');
            $table->bigInteger('entrataDSCRCFmese4');
            $table->bigInteger('entrataDSCRCFmese5');
            $table->bigInteger('entrataDSCRCFmese6');
            $table->bigInteger('uscitaDSCRCFmese1');
            $table->bigInteger('uscitaDSCRCFmese2');
            $table->bigInteger('uscitaDSCRCFmese3');
            $table->bigInteger('uscitaDSCRCFmese4');
            $table->bigInteger('uscitaDSCRCFmese5');
            $table->bigInteger('uscitaDSCRCFmese6');
            $table->bigInteger('rimborsoDSCRmese1');
            $table->bigInteger('rimborsoDSCRmese2');
            $table->bigInteger('rimborsoDSCRmese3');
            $table->bigInteger('rimborsoDSCRmese4');
            $table->bigInteger('rimborsoDSCRmese5');
            $table->bigInteger('rimborsoDSCRmese6');
            $table->bigInteger('agenziaEntrate1');
            $table->bigInteger('agenziaEntrate2');
            $table->bigInteger('agenziaEntrate4');
            $table->bigInteger('INPS1');
            $table->bigInteger('INPS2');
            $table->bigInteger('INPS3');
            $table->bigInteger('riscossione');
            $table->bigInteger('retribuzioni1');
            $table->bigInteger('retribuzioni2');
            $table->bigInteger('retribuzioni3');
            $table->bigInteger('fornitori1');
            $table->bigInteger('fornitori2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('basic');
    }
}
