<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNeedOneOrderDetailStateProductExchangeStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('need_one_order_detail_state_product_exchange_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_exchange_state_id')->unsigned();
            $table->integer('order_detail_state_id')->unsigned();
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
        Schema::dropIfExists('need_one_order_detail_state_product_exchange_state');
    }
}
