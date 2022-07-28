<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccountsTable extends Migration {

	public function up()
	{
		Schema::create('accounts', function(Blueprint $table) {
            $table->id();
			$table->timestamps();
			$table->string('Name');
		});
	}

	public function down()
	{
		Schema::drop('accounts');
	}
}
