<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRoeTable extends Migration {

	public function up()
	{
		Schema::create('roe', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('year');
			$table->decimal('value', 10,2);
		});
	}

	public function down()
	{
		Schema::drop('roe');
	}
}