<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFields extends Migration
{
    public function up()
    {
        Schema::table('ranges', function (Blueprint $table) {
            $table->dropColumn('range_1');
            $table->dropColumn('range_2');
            $table->dropColumn('range_3');
            $table->dropColumn('range_4');
            $table->dropColumn('range_value_1');
            $table->dropColumn('range_value_2');
            $table->dropColumn('range_value_3');
            $table->dropColumn('range_value_4');



            });
        }



    public function down()
    {
        Schema::table('ranges', function($table) {
            $table->float('range_1');
            $table->float('range_2');
            $table->float('range_3');
            $table->float('range_4');
            $table->float('range_value_1');
            $table->float('range_value_2');
            $table->float('range_value_3');
            $table->float('range_value_4');
        });

   }
}


