<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateSchedulefieldsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('schedule_fields', function(Blueprint $table)
		{
			$table->engine = 'MyISAM';

			$table->increments('id');

			$table->integer('rid')->unsigned();
			$table->integer('fid')->unsigned();
			$table->integer('flid')->unsigned();
			$table->mediumText('events');
			$table->timestamps();

			$table->foreign('rid')->references('rid')->on('records')->onDelete('cascade');
			$table->foreign('flid')->references('flid')->on('fields')->onDelete('cascade');
		});

		DB::statement("ALTER TABLE ". env("DB_PREFIX") ."schedule_fields ADD FULLTEXT search_sch(`events`)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table("schedule_fields", function($table) {
			$table->dropIndex("search_sch");
		});
		Schema::drop('schedule_fields');
	}

}