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
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE basic MODIFY COLUMN alertDSCR VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE basic MODIFY COLUMN alertAgenziaEntrate VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE basic MODIFY COLUMN alertINPS VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE basic MODIFY COLUMN alertRiscossione VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE basic MODIFY COLUMN alertRetribuzioni VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE basic MODIFY COLUMN alertFornitori VARCHAR(255) NULL;');
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
