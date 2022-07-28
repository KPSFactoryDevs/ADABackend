<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateForwardLookingTable extends Migration {

	public function up()
	{
		Schema::create('forwardLooking', function(Blueprint $table) {
			$table->increments('id');
			$table->string('question');
			$table->string('answer', 40);
			$table->date('date');
		});
	}

	public function down()
	{
		Schema::drop('forwardLooking');
	}
}