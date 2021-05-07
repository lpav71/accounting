<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderDetailOrderDetailStatePivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_detail_order_detail_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_detail_id')->unsigned()->index();
            $table->integer('order_detail_state_id')->unsigned()->index();
            $table->timestamps();

            $table->foreign('order_detail_id')->references('id')->on('order_details')->onDelete('cascade');
            $table->foreign('order_detail_state_id')->references('id')->on('order_detail_states')->onDelete('cascade');
            $table->index(['order_detail_id', 'order_detail_state_id'], 'order_detail_state_pivot_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_detail_order_detail_state');
    }
}
