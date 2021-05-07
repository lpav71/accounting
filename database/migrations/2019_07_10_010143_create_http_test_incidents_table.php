<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHttpTestIncidentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('http_test_incidents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('http_test_id')->unsigned();
            $table->dateTime('message_time');
            $table->string('http_test_tick_type');
            $table->integer('http_test_tick_id')->unsigned();
            $table->boolean('is_closed')->default(0);
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
        Schema::dropIfExists('http_test_incidents');
    }
}
