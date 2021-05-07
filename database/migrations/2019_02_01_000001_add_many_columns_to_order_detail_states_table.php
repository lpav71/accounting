<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManyColumnsToOrderDetailStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->smallInteger('is_block_editing_order_detail')->unsigned()->default(0);
            $table->smallInteger('is_block_deleting_order_detail')->unsigned()->default(0);
        });

        DB::table('order_detail_states')
            ->where('impact_on_edit_order_detail', 'disable')
            ->update([
                'is_block_editing_order_detail' => 1,
                'is_block_deleting_order_detail' => 1,
            ]);

        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->dropColumn(['impact_on_edit_order_detail']);
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
            $table->string('impact_on_edit_order_detail')->default('enable');
        });

        DB::table('order_detail_states')
            ->where('is_block_editing_order_detail', 1)
            ->update([
                'impact_on_edit_order_detail' => 'disable',
            ]);

        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->dropColumn(
                [
                    'is_block_editing_order_detail',
                    'is_block_deleting_order_detail',
                ]
            );
        });
    }
}
