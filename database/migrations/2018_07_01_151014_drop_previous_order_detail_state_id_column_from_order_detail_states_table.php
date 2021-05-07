<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPreviousOrderDetailStateIdColumnFromOrderDetailStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->dropColumn([
                'previous_order_detail_state_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->integer('previous_order_detail_state_id')->unsigned()->default(0);
        });
    }
}
