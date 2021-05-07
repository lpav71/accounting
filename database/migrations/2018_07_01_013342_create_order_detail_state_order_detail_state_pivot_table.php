<?php

use App\OrderDetailState;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderDetailStateOrderDetailStatePivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_detail_state_order_detail_state', function (Blueprint $table) {
            $table->integer('next_order_detail_state_id')->unsigned()->index('next_order_detail_state');
            $table->integer('order_detail_state_id')->unsigned()->index('order_detail_state');
            $table->timestamps();

            $table->primary(['next_order_detail_state_id', 'order_detail_state_id'], 'next_order_detail_state_index');
        });

        OrderDetailState::all()->each(function (OrderDetailState $orderDetailState) {
            if ($orderDetailState->previous_order_detail_state_id) {
                DB::table('order_detail_state_order_detail_state')->insert([
                    'next_order_detail_state_id' => $orderDetailState->id,
                    'order_detail_state_id' => $orderDetailState->previous_order_detail_state_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_detail_state_order_detail_state');
    }
}
