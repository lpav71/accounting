<?php

use App\Currency;
use App\Store;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrencyIdStoreIdColumnsToOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->integer('currency_id')->unsigned();
            $table->integer('store_id')->unsigned();
        });

        DB::table('order_details')->update(['currency_id' => Currency::first()->id, 'store_id' => Store::first()->id]);

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('store_id')->references('id')->on('stores');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign('order_details_currency_id_foreign');
            $table->dropForeign('order_details_store_id_foreign');
            $table->dropColumn(['currency_id', 'store_id']);
        });
    }
}
