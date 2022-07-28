<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRangeIdToPesiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pesi', function (Blueprint $table) {
            $table->biginteger('range_id')->nullable()->unsigned(); 
            $table->foreign('range_id')->references('id')->on('ranges');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pesi', function (Blueprint $table) {
            //
        });
    }
}
