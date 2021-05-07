<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePivotTableChannelsManagers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channels_managers',function (Blueprint $table){
            $table->increments('id');
            $table->integer('channel_id')->unsigned();
            $table->integer('manager_id')->unsigned();
            $table->timestamps();

            $table->index(['channel_id', 'manager_id'], 'states_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channels_managers');
    }
}
