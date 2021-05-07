<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CourierTaskCourierTaskState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courier_task_courier_task_state',function (Blueprint $table){
            $table->increments('id');
            $table->integer('courier_task_id')->unsigned();
            $table->integer('courier_task_state_id')->unsigned();
            $table->timestamps();

            $table->index(['courier_task_id', 'courier_task_state_id'], 'states_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courier_task_courier_task_state');
    }
}
