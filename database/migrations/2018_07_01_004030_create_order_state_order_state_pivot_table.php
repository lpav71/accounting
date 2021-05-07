<?php

use App\OrderState;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderStateOrderStatePivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_state_order_state', function (Blueprint $table) {
            $table->integer('next_order_state_id')->unsigned()->index();
            $table->integer('order_state_id')->unsigned()->index();
            $table->timestamps();

            $table->primary(['next_order_state_id', 'order_state_id'], 'next_order_state_index');
        });

        OrderState::all()->each(function (OrderState $orderState) {
            if ($orderState->previous_order_state_id) {
                DB::table('order_state_order_state')->insert([
                    'next_order_state_id' => $orderState->id,
                    'order_state_id' => $orderState->previous_order_state_id,
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
        Schema::dropIfExists('order_state_order_state');
    }
}
