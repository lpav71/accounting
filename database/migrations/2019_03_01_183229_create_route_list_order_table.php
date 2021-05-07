<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRouteListOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_list_order', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_list_id')->unsigned();
            $table->integer('order_id')->unsigned();

            $table->unique(['order_id'], 'orders_unique');
            $table->index(['route_list_id', 'order_id'], 'orders_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_list_order');
    }
}
