<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Order;
use App\ProductExchange;
use App\ProductReturn;
use App\RouteList;

class CreateRoutePointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'route_points',
            function (Blueprint $table) {
                $table->increments('id');
                $table->integer('route_list_id')->unsigned();
                $table->string('point_object_type');
                $table->integer('point_object_id')->unsigned();
                $table->boolean('is_point_object_attached')->default(1);
                $table->string('delivery_post_index', 6)->nullable();
                $table->string('delivery_city')->nullable();
                $table->text('delivery_address')->nullable();
                $table->string('delivery_flat', 32)->nullable();
                $table->text('delivery_comment')->nullable();
                $table->time('delivery_start_time')->nullable();
                $table->time('delivery_end_time')->nullable();
                $table->timestamps();
            }
        );

        RouteList::each(
            function (RouteList $routeList) {

                DB::table('route_list_order')
                    ->where('route_list_id', $routeList->id)
                    ->get()
                    ->each(
                        function (stdClass $row) {
                            $order = Order::find($row->order_id);
                            DB::table('route_points')
                                ->insert(
                                    [
                                        'route_list_id' => $row->route_list_id,
                                        'point_object_type' => Order::class,
                                        'point_object_id' => $order->id,
                                        'delivery_post_index' => $order->delivery_post_index,
                                        'delivery_city' => $order->delivery_city,
                                        'delivery_address' => $order->delivery_address,
                                        'delivery_flat' => $order->delivery_address_flat,
                                        'delivery_comment' => $order->delivery_address_comment,
                                        'delivery_start_time' => $order->delivery_start_time,
                                        'delivery_end_time' => $order->delivery_end_time,
                                    ]
                                );
                        }
                    );

                DB::table('route_list_product_return')
                    ->where('route_list_id', $routeList->id)
                    ->get()
                    ->each(
                        function (stdClass $row) {
                            $productReturn = ProductReturn::find($row->product_return_id);
                            DB::table('route_points')
                                ->insert(
                                    [
                                        'route_list_id' => $row->route_list_id,
                                        'point_object_type' => ProductReturn::class,
                                        'point_object_id' => $productReturn->id,
                                        'delivery_post_index' => $productReturn->delivery_post_index,
                                        'delivery_city' => $productReturn->delivery_city,
                                        'delivery_address' => $productReturn->delivery_address,
                                        'delivery_flat' => $productReturn->delivery_flat,
                                        'delivery_comment' => $productReturn->delivery_comment,
                                        'delivery_start_time' => $productReturn->delivery_start_time,
                                        'delivery_end_time' => $productReturn->delivery_end_time,
                                    ]
                                );
                        }
                    );

                DB::table('route_list_product_exchange')
                    ->where('route_list_id', $routeList->id)
                    ->get()
                    ->each(
                        function (stdClass $row) {
                            $productExchange = ProductExchange::find($row->product_exchange_id);
                            DB::table('route_points')
                                ->insert(
                                    [
                                        'route_list_id' => $row->route_list_id,
                                        'point_object_type' => ProductExchange::class,
                                        'point_object_id' => $productExchange->id,
                                        'delivery_post_index' => $productExchange->delivery_post_index,
                                        'delivery_city' => $productExchange->delivery_city,
                                        'delivery_address' => $productExchange->delivery_address,
                                        'delivery_flat' => $productExchange->delivery_flat,
                                        'delivery_comment' => $productExchange->delivery_comment,
                                        'delivery_start_time' => $productExchange->delivery_start_time,
                                        'delivery_end_time' => $productExchange->delivery_end_time,
                                    ]
                                );
                        }
                    );

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
        Schema::dropIfExists('route_points');
    }
}
