<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProductReturnState
 *
 * @property int $id
 * @property string $name
 * @property int|null $new_order_detail_state_id
 * @property bool $check_payment
 * @property string $color
 * @property int $is_successful
 * @property int $is_failure
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needOneOrderDetailStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needOrderDetailStates
 * @property-read \App\OrderDetailState|null $newOrderDetailState
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductReturnState[] $previousStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState whereCheckPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState whereNewOrderDetailStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductReturnState[] $nextStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductReturnState query()
 * @property int $inactive_return
 * @property int $shipment_available
 */
class ProductReturnState extends Model
{
    protected $casts = [
        'check_payment' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'new_order_detail_state_id',
        'check_payment',
        'color',
        'is_successful',
        'is_failure',
        'shipment_available',
        'inactive_return'
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
     * Возможные предыдущие статусы возвратов
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStates()
    {
        return $this->belongsToMany(
            ProductReturnState::class,
            'product_return_state_product_return_state',
            'product_return_state_id',
            'previous_state_id'
        );
    }

    /**
     * Возможные следующие статусы возвратов
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nextStates()
    {
        return $this
            ->belongsToMany(
                ProductReturnState::class,
                'product_return_state_product_return_state',
                'previous_state_id',
                'product_return_state_id');
    }

    /**
     * Статусы товарных позиций, необходимые для смены статуса возврата
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOrderDetailStates()
    {
        return $this->belongsToMany(
            OrderDetailState::class,
            'need_order_detail_state_product_return_state',
            'product_return_state_id',
            'order_detail_state_id'
            )->withTimestamps();
    }

    /**
     * Статусы товарных позиций, в котором должна находится хотя бы одна товарная позиция возврата, необходимые для смены статуса возврата
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOneOrderDetailStates()
    {
        return $this->belongsToMany(
            OrderDetailState::class,
            'need_one_order_detail_state_product_return_state',
            'product_return_state_id',
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
    public function shipAvailable(): bool
    {
        return $this->shipment_available;
    }

    /**
     * @return bool
     */
    public function inactiveReturn(): bool
    {
        return $this->inactive_return;
    }
}
