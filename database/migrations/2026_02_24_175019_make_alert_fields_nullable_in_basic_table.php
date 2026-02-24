<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeAlertFieldsNullableInBasicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('basic', function (Blueprint $table) {
            $table->string('alertDSCR')->nullable()->change();
            $table->string('alertAgenziaEntrate')->nullable()->change();
            $table->string('alertINPS')->nullable()->change();
            $table->string('alertRiscossione')->nullable()->change();
            $table->string('alertRetribuzioni')->nullable()->change();
            $table->string('alertFornitori')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic', function (Blueprint $table) {
            // Reverting to non-nullable is omitted because it could cause data loss
            // or errors if there are null values present.
        });
    }
}
