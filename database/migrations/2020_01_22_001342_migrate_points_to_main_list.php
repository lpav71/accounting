<?php

use App\Role;
use App\RouteList;
use App\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class MigratePointsToMainList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Ищем всех пользователей кто является курьером
        $courierRoles = Role::where('is_courier', 1)->get();
        $couriers = User::all()
            ->filter(
                function (User $user) use ($courierRoles) {
                    return $user->hasAnyRole($courierRoles);
                }
            );

        foreach ($couriers as $courier) {
            //Вытаскиваем маршрутный лист курьера который к нему привязан в связи с новой архитектурой
            $mainRouteList = $courier->routeList;

            if ($mainRouteList) {
                //вытаскиваем все отсавшиеся маршрутные листы
                $otherRouteLists = RouteList::where('id', '!=', $mainRouteList->id)->where(['courier_id' => $courier->id])->get();

                if (!empty($otherRouteLists)) {
                    foreach ($otherRouteLists as $otherRouteList) {
                        if (!empty($otherRouteList->routePoints)) {
                            //Вытаскиваем все маршрутные точки каждого листа
                            /**
                             * @var $routePoint \App\RoutePoint
                             */
                            foreach ($otherRouteList->routePoints as $routePoint) {
                                //Заменяем время доставки из маршрутной точки в заказ
                                switch ($routePoint->point_object_type) {
                                    case 'App\ProductReturn':
                                        $productReturn = \App\ProductReturn::find($routePoint->point_object_id);
                                        $productReturn->delivery_start_time = $routePoint->delivery_start_time;
                                        $productReturn->delivery_end_time = $routePoint->delivery_end_time;
                                        $productReturn->update();
                                        break;
                                    case 'App\ProductExchange':
                                        $productExchange = \App\ProductExchange::find($routePoint->point_object_id);
                                        $productExchange->delivery_start_time = $routePoint->delivery_start_time;
                                        $productExchange->delivery_end_time = $routePoint->delivery_end_time;
                                        $productExchange->update();
                                        break;
                                    case 'App\Order':
                                        $order = \App\Order::find($routePoint->point_object_id);
                                        $order->delivery_start_time = $routePoint->delivery_start_time;
                                        $order->delivery_end_time = $routePoint->delivery_end_time;
                                        $order->update();
                                        break;
                                }
                                //Заменяем id маршрутного листа на главный
                                $routePoint->route_list_id = $mainRouteList->id;
                                $routePoint->update();
                            }
                        }
                        $otherRouteList->delete();
                    }
                }
            }
        }

        //Удаляем столбцы delivery_start_time и delivery_end_time из маршрутных точек за ненадобностью
        Schema::table('route_points', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_start_time',
                'delivery_end_time'
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
        Schema::table('route_points', function (Blueprint $table){
            $table->time('delivery_start_time')->nullable()->default(null);
            $table->time('delivery_end_time')->nullable()->default(null);
        });
    }
}
