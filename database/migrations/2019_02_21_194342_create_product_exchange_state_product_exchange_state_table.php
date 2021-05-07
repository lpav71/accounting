<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductExchangeStateProductExchangeStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_exchange_state_product_exchange_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_exchange_state_id')->unsigned();
            $table->integer('previous_state_id')->unsigned();
            $table->timestamps();

            $table->unique(['product_exchange_state_id', 'previous_state_id'], 'previous_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_exchange_state_product_exchange_state');
    }
}
