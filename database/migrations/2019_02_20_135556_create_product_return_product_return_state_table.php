<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductReturnProductReturnStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_return_product_return_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_return_id')->unsigned();
            $table->integer('product_return_state_id')->unsigned();
            $table->timestamps();

            $table->index(['product_return_id', 'product_return_state_id'], 'states_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_return_product_return_state');
    }
}
