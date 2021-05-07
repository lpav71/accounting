<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRouteListProductExchangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_list_product_exchange', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_list_id')->unsigned();
            $table->integer('product_exchange_id')->unsigned();

            $table->unique(['product_exchange_id'], 'product_exchanges_unique');
            $table->index(['route_list_id', 'product_exchange_id'], 'product_exchanges_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_list_product_exchange');
    }
}
