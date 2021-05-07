<?php

namespace App;

use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\RouteList
 *
 * @property int $id
 * @property int $courier_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $courier
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductExchange[] $productExchanges
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductReturn[] $productReturns
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteList whereCourierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteList whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\RoutePoint[] $routePoints
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteList query()
 * @property-read string $date_list
 */
class RouteList extends Model
{
    use HasBelongsToManyEvents;

    protected $observables = [
        'belongsToManyAttaching',
    ];

    protected $fillable = [
        'courier_id',
    ];

    /**
     * Курьер
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    /**
     * Заказы
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function routePoints()
    {
        return $this->hasMany(RoutePoint::class);
    }

    /**
     * Заказы
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function orders()
    {
        return Order::findMany(
            $this
                ->routePoints()
                ->where('route_points.point_object_type', Order::class)
                ->pluck('point_object_id')
        );
    }

    /**
     * Задачи для курьеров
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function courierTasks()
    {
        return CourierTask::findMany(
            $this->routePoints('route_points.point_object_type', CourierTask::class)
            ->pluck('point_object_id')
        );
    }

    /**
     * Возвраты
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function productReturns()
    {
        return ProductReturn::findMany(
            $this
                ->routePoints()
                ->where('route_points.point_object_type', ProductReturn::class)
                ->pluck('point_object_id')
        );
    }

    /**
     * Обмены
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function productExchanges()
    {
        return ProductExchange::findMany(
            $this
                ->routePoints()
                ->where('route_points.point_object_type', ProductExchange::class)
                ->pluck('point_object_id')
        );
    }

    /**
     * Форматирование даты извлеченной из БД
     *
     * @param $value
     * @return string
     */
    public function getDateListAttribute($value)
    {
        return is_null($value) ? null : Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('d-m-Y');
    }

    /**
     * Получение количества маршрутных точек на сегодня
     *
     * @return int
     */
    public function getCountTodayRoutePoints() : int
    {
        $count = 0;

        //Заказы
        $internalCarriersId = Carrier::where('is_internal', 1)->get()->pluck('id');
        $count += Order::where('is_hidden', 0)
            ->where('date_estimated_delivery', Carbon::now()->format('Y-m-d'))
            ->whereIn('carrier_id', $internalCarriersId)
            ->get()
            ->filter(
                function (Order $order){
                    return $order->active() && $order->shipAvailable() && !is_null($order->routeList()) && $order->routeList()->id == $this->id;
                }
            )
            ->count();

        //Обмены
        $count += ProductExchange::where('delivery_estimated_date', Carbon::now()->format('Y-m-d'))
            ->whereIn('carrier_id', $internalCarriersId)
            ->get()
            ->filter(
                function (ProductExchange $productExchange) {
                    return $productExchange->active() && $productExchange->shipAvailable() && !is_null($productExchange->routeList()) && $productExchange->routeList()->id == $this->id;
                }
            )
            ->count();
        return $count;
    }
}
