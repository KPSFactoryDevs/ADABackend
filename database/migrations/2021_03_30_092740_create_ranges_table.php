<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ranges', function (Blueprint $table) {
            $table->id();
            $table->float('range_1');
            $table->float('range_2');
            $table->float('range_3');
            $table->float('range_4');
            $table->float('range_value_1');
            $table->float('range_value_2');
            $table->float('range_value_3');
            $table->float('range_value_4');

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ranges');
    }
}
