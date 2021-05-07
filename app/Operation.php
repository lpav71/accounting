<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\LaravelFilterable\Filterable;

/**
 * App\Operation
 *
 * @property int $id
 * @property string $type
 * @property int $quantity
 * @property string $comment
 * @property int|null $operable_id
 * @property string|null $operable_type
 * @property int|null $storage_id
 * @property string|null $storage_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $user_id
 * @property int $order_id
 * @property int $certificate_id
 * @property int|null $product_return_id
 * @property int|null $product_exchange_id
 * @property bool $is_reservation
 * @property int $order_detail_id
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $operable
 * @property-read \App\Order $order
 * @property-read \App\OrderDetail $orderDetail
 * @property-read \App\ProductExchange|null $productExchange
 * @property-read \App\ProductReturn|null $productReturn
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $storage
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation filter(\Kyslik\LaravelFilterable\FilterContract $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereIsReservation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereOperableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereOperableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereOrderDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereProductExchangeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereProductReturnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereStorageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereStorageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereUserId($value)
 * @mixin \Eloquent
 * @property bool $is_transfer
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation whereIsTransfer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Operation query()
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OperationState[] $states
 */
class Operation extends Model
{

    use Filterable;

    const STORE_OPERATIONS = [
        'not' => 'No',
        'C' => 'Credit',
        'D' => 'Debit',
    ];

    const OPERATION_TYPES = [
        'C' => 'Credit',
        'D' => 'Debit',
    ];

    const STORE_ORDER_OPERATIONS = [
        'not' => 'No',
        'C' => 'Credit',
        'D' => 'Debit',
        'CR' => 'Reserve',
        'DR' => 'Cancel Reserve',
    ];

    protected $casts = [
        'is_reservation' => 'boolean',
        'is_transfer' => 'boolean',
    ];

    protected $fillable = [
        'type',
        'quantity',
        'comment',
        'user_id',
        'order_id',
        'product_return_id',
        'product_exchange_id',
        'order_detail_id',
        'is_reservation',
        'operable_id',
        'operable_type',
        'storage_id',
        'storage_type',
        'is_transfer',
        'certificate_id'
    ];

    /**
     * Оперируемый объект
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function operable()
    {
        return $this->morphTo();
    }

    /**
     * Хранилище
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function storage()
    {
        return $this->morphTo();
    }

    /**
     * Автор
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * Заказ
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Товарный возврат
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function productReturn()
    {
        return $this->belongsTo(ProductReturn::class);
    }

    /**
     * Товарный обмен
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function productExchange()
    {
        return $this->belongsTo(ProductExchange::class);
    }

    /**
     * Товарная позиция
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    /**
     * Статусы
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function states()
    {
        return $this
            ->belongsToMany(OperationState::class)
            ->withPivot('created_at');
    }

    /**
     * Текущий статус
     *
     * @return mixed
     */
    public function currentState()
    {
        return $this->states()->first();
    }
}
