<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DbMetaController extends Controller
{
    public function schema(Request $request)
    {
        // opzionale: proteggi con auth:api se vuoi
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $db = DB::getDatabaseName();
            $cols = DB::select("
                SELECT TABLE_NAME as table_name, COLUMN_NAME as column_name, DATA_TYPE as data_type
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ?
                ORDER BY TABLE_NAME, ORDINAL_POSITION
            ", [$db]);

            $fks = DB::select("
                SELECT
                    kcu.TABLE_NAME        AS table_name,
                    kcu.COLUMN_NAME       AS column_name,
                    kcu.REFERENCED_TABLE_NAME AS referenced_table,
                    kcu.REFERENCED_COLUMN_NAME AS referenced_column
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                WHERE kcu.TABLE_SCHEMA = ?
                  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ", [$db]);
        } else {
            // fallback generico
            $cols = [];
            $fks  = [];
        }

        // sample 3 righe per tabella (aiuta l'LLM a capire i dati)
        $tables = collect($cols)->pluck('table_name')->unique()->values()->all();
        $samples = [];
        foreach ($tables as $t) {
            try {
                $samples[$t] = DB::table($t)->limit(3)->get();
            } catch (\Throwable $e) {
                $samples[$t] = [];
            }
        }

        return response()->json([
            'driver'  => $driver,
            'columns' => $cols,
            'foreign_keys' => $fks,
            'samples' => $samples,
        ]);
    }
}
