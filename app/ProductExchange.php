<?php

namespace App;

use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\ProductExchange
 *
 * @property int $id
 * @property int $order_id
 * @property string|null $comment
 * @property int|null $carrier_id
 * @property string|null $delivery_shipping_number
 * @property string|null $delivery_post_index
 * @property string|null $delivery_city
 * @property string|null $delivery_address
 * @property string|null $delivery_flat
 * @property string|null $delivery_comment
 * @property string $delivery_estimated_date
 * @property string $delivery_start_time
 * @property string $delivery_end_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Carrier|null $carrier
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetail[] $exchangeOrderDetails
 * @property-read \App\Order $order
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetail[] $orderDetails
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductExchangeState[] $states
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereCarrierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryEstimatedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryFlat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryPostIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryShippingNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereDeliveryStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchange query()
 */
class ProductExchange extends Model
{
    use HasBelongsToManyEvents;

    protected $observables = [
        'belongsToManyAttaching',
        'belongsToManyAttached',
    ];

    protected $fillable = [
        'order_id',
        'carrier_id',
        'comment',
        'delivery_shipping_number',
        'delivery_post_index',
        'delivery_city',
        'delivery_address',
        'delivery_flat',
        'delivery_comment',
        'delivery_estimated_date',
        'delivery_start_time',
        'delivery_end_time',
    ];

    /**
     * Заказ обмена
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Служба доставки
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * История статусов обмена
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function states()
    {
        return $this
            ->belongsToMany(ProductExchangeState::class)
            ->withPivot('created_at')
            ->orderBy('product_exchange_product_exchange_state.id', 'desc');
    }

    /**
     * Текущий статус обмена
     *
     * @return ProductExchangeState
     */
    public function currentState()
    {
        return $this->states->first();
    }

    /**
     * Последующие статусы
     *
     * @return \Illuminate\Support\Collection
     */
    public function nextStates()
    {
        return $this->currentState() ? $this->currentState()->nextStates()->get() : collect([]);
    }

    /**
     * Открыто ли редактирование товарных позиций в обмене
     *
     * @return bool
     */
    public function isOpenEdit()
    {
        return (bool)!$this->currentState() || !$this->currentState()->is_blocked_edit_order_details;
    }

    /**
     * Проверялась ли оплата по обмену
     *
     * @return bool
     */
    public function isCheckedPayment()
    {
        return $this->states()->where('check_payment', '1')->count() > 0;
    }

    /**
     * Товарные позиции обмена
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class)->where('order_details.is_exchange', 0);
    }

    /**
     * Обменные товарные позиции обмена
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exchangeOrderDetails()
    {
        return $this->hasMany(OrderDetail::class)->where('order_details.is_exchange', 1);
    }

    /**
     * Маршрутные точки
     *
     * @return Collection
     */
    public function routePoints()
    {
        return RoutePoint::where('point_object_type', self::class)
            ->where('point_object_id', $this->id)
            ->get();
    }

    /**
     * Текущий маршрутный лист
     *
     * @return RouteList|null
     */
    public function routeList()
    {
        /**
         * @var RoutePoint $routePoint
         */
        $routePoint = $this->routePoints()->where('is_point_object_attached', 1)->first();

        return is_null($routePoint) ? null : $routePoint->routeList;
    }

    /**
     * Получение адреса доставки одной строкой
     *
     * @return string
     */
    public function getFullAddress()
    {
        return collect(
            [
                'delivery_post_index' => $this->delivery_post_index,
                'delivery_city' => $this->delivery_city,
                'delivery_address' => $this->delivery_address,
                'delivery_flat' => $this->delivery_flat,
                'delivery_comment' => $this->delivery_comment,
            ]
        )
            ->filter(
                function ($item) {
                    return !is_null($item) && $item !== '';
                }
            )
            ->map(
                function ($item, $key) {
                    return $key == 'delivery_flat' ? __('fl').' '.$item : $item;
                }
            )
            ->implode(', ');
    }

    /**
     * Адрес для карты
     *
     * @return string
     */
    public function getMapDeliveryAddress()
    {
        return collect(
            [
                'delivery_post_index' => $this->delivery_post_index,
                'delivery_city' => $this->delivery_city,
                'delivery_address' => $this->delivery_address,
                'delivery_flat' => $this->delivery_flat,
            ]
        )->filter(
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        )->map(
            function ($item, $key) {
                return $key == 'delivery_flat' ? __('fl').' '.$item : $item;
            }
        )->implode(', ');
    }

    /**
     * Форматирование расчетной даты доставки извлеченной из БД
     *
     * @param $value
     * @return string
     */
    public function getDeliveryEstimatedDateAttribute($value)
    {
        return is_null($value) ? null : Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('d-m-Y');
    }

    /**
     * Форматирование расчетной даты доставки для сохранения в БД
     *
     * @param $value
     */
    public function setDeliveryEstimatedDateAttribute($value)
    {
        $this->attributes['delivery_estimated_date'] =
            is_null($value) ? null : Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
    }

    /**
     * Форматирование расчетного начального времени доставки извлеченного из БД
     *
     * @param $value
     * @return string
     */
    public function getDeliveryStartTimeAttribute($value)
    {
        return is_null($value) ? $value : Carbon::createFromFormat('H:i:s', $value)->format('H:i');
    }

    /**
     * Форматирование расчетного начального времени доставки для сохранения в БД
     *
     * @param $value
     */
    public function setDeliveryStartTimeAttribute($value)
    {
        $this->attributes['delivery_start_time'] =
            is_null($value) ? $value : Carbon::createFromFormat('H:i', $value)->format('H:i:s');
    }

    /**
     * Форматирование расчетного конечного времени доставки извлеченного из БД
     *
     * @param $value
     * @return string
     */
    public function getDeliveryEndTimeAttribute($value)
    {
        return is_null($value) ? $value : Carbon::createFromFormat('H:i:s', $value)->format('H:i');
    }

    /**
     * Форматирование расчетного конечного времени доставки для сохранения в БД
     *
     * @param $value
     */
    public function setDeliveryEndTimeAttribute($value)
    {
        $this->attributes['delivery_end_time'] =
            is_null($value) ? $value : Carbon::createFromFormat('H:i', $value)->format('H:i:s');
    }

    /**
     * @return string
     */
    public function getStreetDeliveryAddress()
    {
        return collect(
            [
                'delivery_address' => $this->delivery_address,
                'delivery_address_flat' => $this->delivery_address_flat,
                'delivery_address_comment' => $this->delivery_address_comment,
                'pickup_point_address' => $this->pickup_point_address,
            ]
        )->filter(
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        )->map(
            function ($item, $key) {
                return $key == 'delivery_address_flat' ? __('fl').' '.$item : ($key == 'pickup_point_address' ? __(
                        'Pickup point'
                    ).': '.$item : $item);
            }
        )->implode(', ');
    }

    /**
     * Получение следующего статуса обмена в зависимости от статусов товаров
     *
     * @return mixed
     */
    public function getNextExchangeStateDependingOrderDetailStates()
    {
        $states = [];
        /**
         * @var $orderDetail OrderDetail
         */
        foreach ($this->orderDetails as $orderDetail) {
            if($orderDetail->currentState()->is_delivered) {
                $states[] = 1;
            } else {
                $states[] = 0;
            }
        }

        if(in_array(1, $states)) {
            return $this->nextStates()->where('is_successful', 1)->first();
        }

        return $this->nextStates()->where('is_failure', 1)->first();
    }

    /**
     * @return bool
     */
    public function shipAvailable():bool
    {
        return $this->currentState()->shipAvailable();
    }

    /**
     * @return bool
     */
    public function active():bool
    {
        return !$this->currentState()->inactiveExchange();
    }
}
