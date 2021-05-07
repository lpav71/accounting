<?php

use App\OrderDetailState;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFullNameColumnToOrderDetailStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'order_detail_states',
            function (Blueprint $table) {
                $table->string('full_name')->after('name');
            }
        );


        OrderDetailState::each(
            function (OrderDetailState $orderDetailState) {
                $orderDetailState->update(['full_name' => $orderDetailState->name]);
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'order_detail_states',
            function (Blueprint $table) {
                $table->dropColumn(['full_name']);
            }
        );
    }
}
