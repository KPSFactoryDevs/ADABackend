<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionarioTable extends Migration {

	public function up()
	{
		Schema::create('questionario', function(Blueprint $table) {
			$table->increments('id');
			$table->string('parameter', 40);
			$table->string('result', 4);
			$table->date('date');
			$table->longText('details')->nullable();
		});
	}

	public function down()
	{
		Schema::drop('questionario');
	}
}