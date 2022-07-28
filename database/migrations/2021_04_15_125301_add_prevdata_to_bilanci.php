<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrevdataToBilanci extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bilanci', function (Blueprint $table) {
            $table->longText('json_data_prev');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bilanci', function (Blueprint $table) {
            $table->dropColumn('json_data_prev');
        });
    }
}
