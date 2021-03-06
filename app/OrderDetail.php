<?php

namespace App;

use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Model;

/**
 * App\OrderDetail
 *
 * @property int $id
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $order_id
 * @property float $price
 * @property int $currency_id
 * @property int $store_id
 * @property int $printing_group
 * @property string $owner_type
 * @property int $product_return_id
 * @property int $product_exchange_id
 * @property bool $is_exchange
 * @property-read \App\Currency $currency
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @property-read \App\Order $order
 * @property-read \App\Product $product
 * @property-read \App\ProductExchange $productExchange
 * @property-read \App\ProductReturn $productReturn
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $states
 * @property-read \App\Store $store
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereIsExchange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail wherePrintingGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereProductExchangeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereProductReturnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetail query()
 */
class OrderDetail extends Model
{
    use HasBelongsToManyEvents;

    const OWNERS = [
        'App\Order' => 'Order',
        'App\ProductReturn' => 'Return',
        'App\ProductExchange' => 'Exchange',
    ];

    protected $observables = [
        'belongsToManyAttaching',
        'belongsToManyAttached',
    ];

    protected $casts = [
        'owner_type' => 'string',
        'is_exchange' => 'boolean',
    ];

    protected $fillable = [
        'product_id',
        'order_id',
        'price',
        'currency_id',
        'store_id',
        'printing_group',
        'owner_type',
        'product_return_id',
        'product_exchange_id',
        'is_exchange',
    ];

    /**
     * ???????????? ?????????????????? ?? ???????????????????????????? ??????????????????
     *
     * @var array
     */
    protected $editableAlways = [
        'price',
        'currency_id',
        'store_id',
        'updated_at',
        'printing_group',
        'owner_type',
        'product_return_id',
        'product_exchange_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    /**
     * ??????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ????????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * ????????????????
     *
     * @return Order|ProductExchange|ProductReturn|null
     */
    public function owner()
    {
        switch ($this->owner_type) {
            case Order::class:
                return $this->order;
                break;
            case ProductReturn::class:
                return $this->productReturn;
                break;
            case ProductExchange::class:
                return $this->productExchange;
                break;
        }

        return null;
    }

    /**
     * ??????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * ??????????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productReturn()
    {
        return $this->belongsTo(ProductReturn::class);
    }

    /**
     * ??????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productExchange()
    {
        return $this->belongsTo(ProductExchange::class);
    }

    /**
     * ??????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * ??????????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function states()
    {
        return $this
            ->belongsToMany(OrderDetailState::class)
            ->withPivot('created_at')
            ->orderBy('order_detail_order_detail_state.id', 'desc');
    }

    /**
     * ????????????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function operations()
    {
        return $this->hasMany(Operation::class);
    }

    /**
     * ?????????????? ????????????
     *
     * @return \App\OrderDetailState
     */
    public function currentState()
    {
        return $this->states()->first();
    }

    /**
     * ?????????????????????? ??????????????
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Support\Collection
     */
    public function nextStates()
    {
        $currentState = OrderDetailState::find($this->currentState()['id']);

        return $currentState ? $currentState->nextStates() : collect([]);
    }

    /**
     * ???????????? ???? ?????????? ????????????, ?????????????????? ????????????
     *
     * @return bool
     */
    public function isPayable()
    {
        return $this->states->where('need_payment', 1)->isNotEmpty();
    }

    /**
     * ?????????????????????????? ???? ???????????????? ?? ????????????
     *
     * @return bool
     */
    public function isBlockedDelete()
    {
        return $this->currentState()->is_block_deleting_order_detail || $this->order->isCheckedPayment();
    }

    /**
     * ?????????????????????????? ???? ???????????????? ?? ????????????
     *
     * @return bool
     */
    public function isBlockedDeleteExchange()
    {
        return $this->currentState()->is_block_deleting_order_detail || $this->productExchange->isCheckedPayment();
    }

    /**
     * ?????????????????????????? ???? ???????????????????????????? ?? ????????????
     *
     * @return bool
     */
    public function isBlockedEdit()
    {
        return $this->currentState()->is_block_editing_order_detail || $this->order->isCheckedPayment();
    }

    /**
     * ?????????????????????????? ???? ???????????????????????????? ?? ????????????
     *
     * @return bool
     */
    public function isBlockedEditExchange()
    {
        return $this->currentState()->is_block_editing_order_detail || $this->productExchange->isCheckedPayment();
    }

    /**
     * ?????????????????? ???????????? ???????????? ?????????????????? ?? ???????????????????????????? ????????????????????
     *
     * @return array
     */
    public function getEditableAlways()
    {
        return $this->editableAlways;
    }


    /**
     * @return bool
     */
    public function isSendOrReserved()
    {
        $debit = Operation::
        where('order_detail_id', $this->id)
            ->where('type', 'D')
            ->sum('quantity');
        $credit = Operation::
        where('order_detail_id', $this->id)
            ->where('type', 'C')
            ->sum('quantity');
        return $debit != $credit;
    }
}
