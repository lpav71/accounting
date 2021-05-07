<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductExchangeStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_exchange_states', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('new_order_detail_state_id')->unsigned()->nullable();
            $table->integer('new_exchange_order_detail_state_id')->unsigned()->nullable();
            $table->boolean('check_payment')->default(0);
            $table->string('color')->default('#FFFFFF');
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
        Schema::dropIfExists('product_exchange_states');
    }
}
