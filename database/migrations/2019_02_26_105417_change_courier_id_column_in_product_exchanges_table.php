<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCourierIdColumnInProductExchangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('product_exchanges')
            ->whereNull('courier_id')
            ->update(['courier_id' => 0]);

        Schema::table('product_exchanges', function (Blueprint $table) {
            $table->integer('courier_id')->unsigned()->nullable(false)->default(0)->change();
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
            $table->integer('courier_id')->unsigned()->nullable()->change();
        });
    }
}
