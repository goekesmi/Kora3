<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BackupSupport extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::create('backup_support', function(Blueprint $table)
		{
			$table->engine = 'MyISAM';

			$table->increments('id');
			$table->integer('user_id')->unsigned();
			$table->dateTime('hasRun')->nullable();
			$table->integer('accessed');
			$table->text('view');
			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
		Schema::drop('backup_support');
	}

}
