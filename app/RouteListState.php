<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\RouteListState
 *
 * @property int $id
 * @property string $name
 * @property bool $is_editable_route_list
 * @property bool $is_create_currency_operations
 * @property bool $is_successful
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needOrderDetailStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderState[] $needOrderStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductExchangeState[] $needProductExchangeStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductReturnState[] $needProductReturnStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\RouteListState[] $previousStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereIsCreateCurrencyOperations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereIsEditableRouteList($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereIsSuccessful($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property bool $is_deletable_route_points
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereIsDeletableRoutePoints($value)
 * @property string $color
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RouteListState query()
 */
class RouteListState extends Model
{
    protected $casts = [
        'name' => 'string',
        'is_editable_route_list' => 'boolean',
        'is_create_currency_operations' => 'boolean',
        'is_deletable_route_points' => 'boolean',
        'is_successful' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'is_editable_route_list',
        'is_deletable_route_points',
        'is_create_currency_operations',
        'color',
        'is_successful'
    ];

//    /**
//     * Возможные предыдущие статусы для статуса машрутного листа
//     *
//     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
//     */
//    public function previousStates()
//    {
//        return $this->belongsToMany(
//            RouteListState::class,
//            'route_list_state_route_list_state',
//            'route_list_state_id',
//            'previous_state_id'
//        );
//    }

    /**
     * Статусы товарных позиций, необходимые для смены статуса маршрутного листа
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOrderDetailStates()
    {
        return $this->belongsToMany(
            OrderDetailState::class,
            'need_order_detail_state_route_list_state',
            'route_list_state_id',
            'order_detail_state_id'
        );
    }

    /**
     * Статусы заказов, необходимые для смены статуса маршрутного листа
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOrderStates()
    {
        return $this->belongsToMany(
            OrderState::class,
            'need_order_state_route_list_state',
            'route_list_state_id',
            'order_state_id'
        );
    }

    /**
     * Статусы возвратов, необходимые для смены статуса маршрутного листа
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needProductReturnStates()
    {
        return $this->belongsToMany(
            ProductReturnState::class,
            'need_product_return_state_route_list_state',
            'route_list_state_id',
            'product_return_state_id'
        );
    }

    /**
     * Статусы обменов, необходимые для смены статуса маршрутного листа
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needProductExchangeStates()
    {
        return $this->belongsToMany(
            ProductExchangeState::class,
            'need_product_exchange_state_route_list_state',
            'route_list_state_id',
            'product_exchange_state_id'
        );
    }

    /**
     * Новый статус товарных позиций для статуса товарной позиции при смене статуса маршрутного листа
     *
     * @param OrderDetailState $orderDetailState
     * @return \Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function newOrderDetailState(OrderDetailState $orderDetailState)
    {
        return OrderDetailState::find(
            $this->newOrderDetailStateId($orderDetailState->id)
        );

    }

    /**
     * Id Нового статуса товарных позиций для статуса товарной позиции при смене статуса маршрутного листа
     *
     * @param int $orderDetailStateId
     * @return int|false
     */
    public function newOrderDetailStateId($orderDetailStateId)
    {
        return DB::table('route_list_state_new_order_detail_state')
            ->where('route_list_state_id', $this->id)
            ->where('current_order_detail_state_id', $orderDetailStateId)
            ->value('new_order_detail_state_id');

    }

    /**
     * Обновление Нового статуса товарных позиций для статуса товарной позиции при смене статуса маршрутного листа
     *
     * @param int $currentOrderDetailStateId
     * @param int $newOrderDetailStateId
     * @return bool
     */
    public function updateNewOrderDetailState($currentOrderDetailStateId, $newOrderDetailStateId)
    {
        if (OrderDetailState::query()
                ->where('id', $currentOrderDetailStateId)
                ->exists()

            && OrderDetailState::query()
                ->where('id', $newOrderDetailStateId)
                ->exists()) {


            DB::table('route_list_state_new_order_detail_state')
                ->where('route_list_state_id', $this->id)
                ->where('current_order_detail_state_id', $currentOrderDetailStateId)
                ->delete();

            return DB::table('route_list_state_new_order_detail_state')
                ->insert(
                    [
                        'route_list_state_id' => $this->id,
                        'current_order_detail_state_id' => $currentOrderDetailStateId,
                        'new_order_detail_state_id' => $newOrderDetailStateId,
                    ]
                );

        }

        return false;

    }

    /**
     * Новый статус заказа для статуса заказа при смене статуса маршрутного листа
     *
     * @param OrderState $orderState
     * @return \Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function newOrderState(OrderState $orderState)
    {
        return OrderState::find(
            $this->newOrderStateId($orderState->id)
        );

    }

    /**
     * Id нового статуса заказа для статуса заказа при смене статуса маршрутного листа
     *
     * @param int $orderStateId
     * @return int|false
     */
    public function newOrderStateId($orderStateId)
    {
        return DB::table('route_list_state_new_order_state')
            ->where('route_list_state_id', $this->id)
            ->where('current_order_state_id', $orderStateId)
            ->value('new_order_state_id');

    }

    /**
     * Обновление Нового статуса заказа для статуса заказа при смене статуса маршрутного листа
     *
     * @param int $currentOrderStateId
     * @param int $newOrderStateId
     * @return bool
     */
    public function updateNewOrderState($currentOrderStateId, $newOrderStateId)
    {
        if (OrderState::query()
                ->where('id', $currentOrderStateId)
                ->exists()

            && OrderState::query()
                ->where('id', $newOrderStateId)
                ->exists()) {


            DB::table('route_list_state_new_order_state')
                ->where('route_list_state_id', $this->id)
                ->where('current_order_state_id', $currentOrderStateId)
                ->delete();

            return DB::table('route_list_state_new_order_state')
                ->insert(
                    [
                        'route_list_state_id' => $this->id,
                        'current_order_state_id' => $currentOrderStateId,
                        'new_order_state_id' => $newOrderStateId,
                    ]
                );

        }

        return false;

    }

    /**
     * Новый статус возврата для статуса возврата при смене статуса маршрутного листа
     *
     * @param ProductReturnState $productReturnState
     * @return \Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function newProductReturnState(ProductReturnState $productReturnState)
    {
        return ProductReturnState::find(
            $this->newProductReturnStateId($productReturnState->id)
        );

    }

    /**
     * Id нового статуса возврата для статуса возврата при смене статуса маршрутного листа
     *
     * @param int $productReturnStateId
     * @return int|false
     */
    public function newProductReturnStateId($productReturnStateId)
    {
        return DB::table('route_list_state_new_product_return_state')
            ->where('route_list_state_id', $this->id)
            ->where('current_product_return_state_id', $productReturnStateId)
            ->value('new_product_return_state_id');

    }

    /**
     * Обновление Нового статуса возврата для статуса возврата при смене статуса маршрутного листа
     *
     * @param int $currentProductReturnStateId
     * @param int $newProductReturnStateId
     * @return bool
     */
    public function updateNewProductReturnState($currentProductReturnStateId, $newProductReturnStateId)
    {
        if (OrderState::query()
                ->where('id', $currentProductReturnStateId)
                ->exists()

            && OrderState::query()
                ->where('id', $newProductReturnStateId)
                ->exists()) {


            DB::table('route_list_state_new_product_return_state')
                ->where('route_list_state_id', $this->id)
                ->where('current_product_return_state_id', $currentProductReturnStateId)
                ->delete();

            return DB::table('route_list_state_new_product_return_state')
                ->insert(
                    [
                        'route_list_state_id' => $this->id,
                        'current_product_return_state_id' => $currentProductReturnStateId,
                        'new_product_return_state_id' => $newProductReturnStateId,
                    ]
                );

        }

        return false;

    }

    /**
     * Новый статус обмена для статуса обмена при смене статуса маршрутного листа
     *
     * @param ProductExchangeState $productExchangeState
     * @return \Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function newProductExchangeState(ProductExchangeState $productExchangeState)
    {
        return ProductExchangeState::find(
            $this->newProductExchangeStateId($productExchangeState->id)
        );

    }

    /**
     * Id нового статуса обмена для статуса обмена при смене статуса маршрутного листа
     *
     * @param int $productExchangeStateId
     * @return int|false
     */
    public function newProductExchangeStateId($productExchangeStateId)
    {
        return DB::table('route_list_state_new_product_exchange_state')
            ->where('route_list_state_id', $this->id)
            ->where('current_product_exchange_state_id', $productExchangeStateId)
            ->value('new_product_exchange_state_id');

    }

    /**
     * Обновление Нового статуса обмена для статуса обмена при смене статуса маршрутного листа
     *
     * @param int $currentProductExchangeStateId
     * @param int $newProductExchangeStateId
     * @return bool
     */
    public function updateNewProductExchangeState($currentProductExchangeStateId, $newProductExchangeStateId)
    {
        if (OrderState::query()
                ->where('id', $currentProductExchangeStateId)
                ->exists()

            && OrderState::query()
                ->where('id', $newProductExchangeStateId)
                ->exists()) {


            DB::table('route_list_state_new_product_exchange_state')
                ->where('route_list_state_id', $this->id)
                ->where('current_product_exchange_state_id', $currentProductExchangeStateId)
                ->delete();

            return DB::table('route_list_state_new_product_exchange_state')
                ->insert(
                    [
                        'route_list_state_id' => $this->id,
                        'current_product_exchange_state_id' => $currentProductExchangeStateId,
                        'new_product_exchange_state_id' => $newProductExchangeStateId,
                    ]
                );

        }

        return false;

    }

    /**
     * Форматирование цвета для сохранения в БД
     *
     * @param $value
     */
    public function setColorAttribute($value)
    {
        $this->attributes['color'] = is_null($value) ? $value : strtoupper($value);
    }
}
