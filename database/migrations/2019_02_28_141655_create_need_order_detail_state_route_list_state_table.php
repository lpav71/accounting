<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNeedOrderDetailStateRouteListStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('need_order_detail_state_route_list_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_list_state_id')->unsigned();
            $table->integer('order_detail_state_id')->unsigned();

            $table->unique(['route_list_state_id', 'order_detail_state_id'], 'need_states');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('need_order_detail_state_route_list_state');
    }
}
