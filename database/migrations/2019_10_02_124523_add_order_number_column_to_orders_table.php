<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderNumberColumnToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('order_number')->unsigned()->nullable()->after('id');
        });

        \App\Order::each(
            function (\App\Order $order) {

                DB::query()
                    ->from('orders')
                    ->where(['id' => $order->id])
                    ->update(['order_number' => $order->generateRandomId('order_number')]);

                $order->refresh();

            }
        );

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('order_number')->unsigned()->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_number');
        });
    }
}
