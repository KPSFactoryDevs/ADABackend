<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyAlertColumnsToVarcharInAnalisiTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE analisi_agenziaEntrate MODIFY COLUMN alertAgenziaEntrate VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE analisi_inps MODIFY COLUMN alertINPS VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE analisi_riscossione MODIFY COLUMN alertRiscossione VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE analisi_retribuzioni MODIFY COLUMN alertRetribuzioni VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE analisi_fornitori MODIFY COLUMN alertFornitori VARCHAR(255) NULL;');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE analisi_dscr MODIFY COLUMN alertDSCR VARCHAR(255) NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reverting not fully supported or safely implemented by default since data might have strings.
    }
}
