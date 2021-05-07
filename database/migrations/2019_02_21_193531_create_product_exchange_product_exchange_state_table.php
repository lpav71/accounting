<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductExchangeProductExchangeStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_exchange_product_exchange_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_exchange_id')->unsigned();
            $table->integer('product_exchange_state_id')->unsigned();
            $table->timestamps();

            $table->index(['product_exchange_id', 'product_exchange_state_id'], 'states_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_exchange_product_exchange_state');
    }
}
