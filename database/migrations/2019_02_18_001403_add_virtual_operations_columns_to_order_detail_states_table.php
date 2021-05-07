<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVirtualOperationsColumnsToOrderDetailStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->string('currency_operation_by_order')->default('not');
            $table->string('product_operation_by_order')->default('not');
        });

        DB::table('order_detail_states')
            ->where('need_payment', 1)
            ->update(
                [
                    'currency_operation_by_order' => 'D',
                    'product_operation_by_order' => 'D',
                ]
            );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->dropColumn([
                'currency_operation_by_order',
                'product_operation_by_order',
            ]);
        });
    }
}
