<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRouteListProductReturnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_list_product_return', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_list_id')->unsigned();
            $table->integer('product_return_id')->unsigned();

            $table->unique(['product_return_id'], 'product_returns_unique');
            $table->index(['route_list_id', 'product_return_id'], 'product_returns_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_list_product_return');
    }
}
