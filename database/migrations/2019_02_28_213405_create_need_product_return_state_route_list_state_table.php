<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNeedProductReturnStateRouteListStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('need_product_return_state_route_list_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_list_state_id')->unsigned();
            $table->integer('product_return_state_id')->unsigned();

            $table->unique(['route_list_state_id', 'product_return_state_id'], 'need_states');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('need_product_return_state_route_list_state');
    }
}
