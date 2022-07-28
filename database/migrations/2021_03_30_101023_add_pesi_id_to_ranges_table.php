<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPesiIdToRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ranges', function (Blueprint $table) {
            $table->biginteger('pesi_id')->nullable()->unsigned(); 

            $table->foreign('pesi_id')->references('id')->on('pesi');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ranges', function (Blueprint $table) {
            //
        });
    }
}
