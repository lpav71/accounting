<?php

use App\Channel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeliveryColumnsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('date_estimated_delivery')->nullable();
            $table->string('delivery_city')->nullable();
            $table->text('delivery_address')->nullable();
            $table->integer('carrier_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('channel_id')->unsigned();
            $table->string('delivery_shipping_number')->nullable();
            $table->string('delivery_start_time')->nullable();
            $table->string('delivery_end_time')->nullable();
        });

        DB::table('orders')->update(['channel_id' => Channel::all()->first()->id]);

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('channel_id')->references('id')->on('channels');
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
            $table->dropForeign('orders_channel_id_foreign');
            $table->dropColumn([
                'date_estimated_delivery',
                'delivery_city',
                'delivery_address',
                'carrier_id',
                'user_id',
                'channel_id',
                'delivery_shipping_number',
                'delivery_start_time',
                'delivery_end_time',
            ]);
        });
    }
}
