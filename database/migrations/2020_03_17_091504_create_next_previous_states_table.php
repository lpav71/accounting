<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNextPreviousStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courier_task_state_courier_task_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('courier_task_state_id')->unsigned();
            $table->integer('previous_state_id')->unsigned();
            $table->timestamps();

            $table->unique(['courier_task_state_id', 'previous_state_id'], 'previous_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courier_task_state_courier_task_state');
    }
}
