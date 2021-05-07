<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropCourierIdColumnFromProductExchangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_exchanges', function (Blueprint $table) {
            $table->dropColumn(['courier_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_exchanges', function (Blueprint $table) {
            $table->integer('courier_id')->unsigned()->nullable()->after('order_id');
        });
    }
}
