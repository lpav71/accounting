<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProductExchangeState
 *
 * @property int $id
 * @property string $name
 * @property int|null $new_order_detail_state_id
 * @property int|null $new_exchange_order_detail_state_id
 * @property bool $check_payment
 * @property string $color
 * @property int $is_successful
 * @property int $is_failure
 * @property int $is_sent
 * @property int $next_auto_closing_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_blocked_edit_order_details
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needExchangeOrderDetailStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needOneExchangeOrderDetailStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needOneOrderDetailStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needOrderDetailStates
 * @property-read \App\OrderDetailState|null $newExchangeOrderDetailState
 * @property-read \App\OrderDetailState|null $newOrderDetailState
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductExchangeState[] $previousStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereCheckPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereIsBlockedEditOrderDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereNewExchangeOrderDetailStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereNewOrderDetailStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductExchangeState[] $nextStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductExchangeState query()
 * @property int $inactive_exchange
 * @property int $shipment_available
 */
class ProductExchangeState extends Model
{
    protected $casts = [
        'check_payment' => 'boolean',
        'is_blocked_edit_order_details' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'new_order_detail_state_id',
        'new_exchange_order_detail_state_id',
        'is_blocked_edit_order_details',
        'check_payment',
        'color',
        'is_successful',
        'is_failure',
        'is_sent',
        'next_auto_closing_status',
        'inactive_exchange',
        'shipment_available'
    ];

    /**
     * Новый статус товарных позиций
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function newOrderDetailState()
    {
        return $this->belongsTo(OrderDetailState::class, 'new_order_detail_state_id');
    }

    /**
     * Новый статус обменных товарных позиций
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function newExchangeOrderDetailState()
    {
        return $this->belongsTo(OrderDetailState::class, 'new_exchange_order_detail_state_id');
    }

    /**
     * Возможные предыдущие статусы обменов
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStates()
    {
        return $this->belongsToMany(
            ProductExchangeState::class,
            'product_exchange_state_product_exchange_state',
            'product_exchange_state_id',
            'previous_state_id'
        );
    }

    /**
     * Возможные следующие статусы обменов
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nextStates()
    {
        return $this
            ->belongsToMany(
                ProductExchangeState::class,
                'product_exchange_state_product_exchange_state',
                'previous_state_id',
                'product_exchange_state_id');
    }

    /**
     * Статусы товарных позиций, необходимые для смены статуса обмена
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOrderDetailStates()
    {
        return $this->belongsToMany(
            OrderDetailState::class,
            'need_order_detail_state_product_exchange_state',
            'product_exchange_state_id',
            'order_detail_state_id'
        )->withTimestamps();
    }

    /**
     * Статусы обменных товарных позиций, необходимые для смены статуса обмена
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needExchangeOrderDetailStates()
    {
        return $this->belongsToMany(
            OrderDetailState::class,
            'need_exchange_order_detail_state_product_exchange_state',
            'product_exchange_state_id',
            'order_detail_state_id'
        )->withTimestamps();
    }

    /**
     * Статусы товарных позиций, в котором должна находится хотя бы одна товарная позиция обмена, необходимые для смены статуса обмена
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOneOrderDetailStates()
    {
        return $this->belongsToMany(
            OrderDetailState::class,
            'need_one_order_detail_state_product_exchange_state',
            'product_exchange_state_id',
            'order_detail_state_id'
        )->withTimestamps();
    }

    /**
     * Статусы обменных товарных позиций, в котором должна находится хотя бы одна товарная позиция обмена, необходимые для смены статуса обмена
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOneExchangeOrderDetailStates()
    {
        return $this->belongsToMany(
            OrderDetailState::class,
            'need_one_exchange_order_detail_state_product_exchange_state',
            'product_exchange_state_id',
            'order_detail_state_id'
        )->withTimestamps();
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

    /**
     * @return bool
     */
    public function inactiveExchange():bool
    {
        return $this->inactive_exchange;
    }

    /**
     * @return bool
     */
    public function shipAvailable():bool
    {
        return $this->shipment_available;
    }
}
