<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsBlockEditingStoreToOrderDetailStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->boolean('is_block_editing_store')->default(0)->after('is_block_deleting_order_detail');
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
            $table->dropColumn(['is_block_editing_store']);
        });
    }
}
