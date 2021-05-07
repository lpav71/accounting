<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNeedOneOrderDetailStateOrderStatePivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('need_one_order_detail_state_order_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_detail_state_id')->unsigned()->index('one_order_detail_state_order_state_order_detail_state_id_index');
            $table->integer('order_state_id')->unsigned()->index();
            $table->timestamps();

            $table->foreign('order_detail_state_id', 'need_one_order_detail_state_foreign')->references('id')->on('order_detail_states')->onDelete('cascade');
            $table->foreign('order_state_id')->references('id')->on('order_states')->onDelete('cascade');
            $table->index(['order_state_id', 'order_detail_state_id'], 'one_order_state_order_detail_state_pivot_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('need_one_order_detail_state_order_state');
    }
}
