<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

//TODO Отрефакторить контроллер, модель, FormRequest по примеру ProductReturnState
/**
 * App\OrderDetailState
 *
 * @property int $id
 * @property string $name
 * @property bool $is_courier_state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $is_hidden
 * @property int $is_delivered
 * @property int $is_returned
 * @property int $is_sent
 * @property int $is_reserved
 * @property int $is_shipped
 * @property string $store_operation
 * @property int $need_payment
 * @property bool $is_block_editing_order_detail
 * @property bool $is_block_deleting_order_detail
 * @property bool $crediting_certificate
 * @property bool $writing_off_certificate
 * @property bool $zeroing_certificate_number
 * @property string $currency_operation_by_order
 * @property string $product_operation_by_order
 * @property string $owner_type
 * @property string|null $new_order_detail_owner_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $nextStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetail[] $orderDetails
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $previousStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereCurrencyOperationByOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereIsBlockDeletingOrderDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereIsBlockEditingOrderDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereIsCourierState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereIsHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereNeedPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereNewOrderDetailOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereProductOperationByOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereStoreOperation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property bool $is_block_editing_store
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereIsBlockEditingStore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState query()
 * @property string $full_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderDetailState whereFullName($value)
 * @property int $is_new
 */
class OrderDetailState extends Model
{
    const OWNERS = [
        'App\Order' => 'Order',
        'App\ProductReturn' => 'Return',
        'App\ProductExchange' => 'Exchange',
    ];

    protected $casts = [
        'is_courier_state' => 'boolean',
        'is_block_editing_order_detail' => 'boolean',
        'is_block_deleting_order_detail' => 'boolean',
        'is_block_editing_store' => 'boolean',
        'is_delivered' => 'boolean',
        'is_returned' => 'boolean',
        'is_sent' => 'boolean',
        'is_reserved' => 'boolean',
        'is_shipped' => 'boolean',
        'crediting_certificate' => 'boolean',
        'writing_off_certificate' => 'boolean',
        'zeroing_certificate_number' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'full_name',
        'is_courier_state',
        'is_hidden',
        'store_operation',
        'need_payment',
        'is_block_editing_order_detail',
        'is_block_deleting_order_detail',
        'is_block_editing_store',
        'currency_operation_by_order',
        'product_operation_by_order',
        'owner_type',
        'new_order_detail_owner_type',
        'is_delivered',
        'is_returned',
        'is_sent',
        'is_new',
        'is_reserved',
        'is_shipped',
        'crediting_certificate',
        'writing_off_certificate',
        'zeroing_certificate_number'
    ];

    /**
     * Get Order Detail State's order detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orderDetails()
    {
        return $this->belongsToMany('App\OrderDetail')->withTimestamps();
    }

    /**
     * Get Order detail state's previous states
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStates()
    {
        return $this->belongsToMany('App\OrderDetailState', 'order_detail_state_order_detail_state',
            'next_order_detail_state_id', 'order_detail_state_id');
    }

    /**
     * Get Order detail state's next states
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nextStates()
    {
        return $this->belongsToMany('App\OrderDetailState', 'order_detail_state_order_detail_state',
            'order_detail_state_id', 'next_order_detail_state_id');
    }

    /**
     * Get Impacts On Order Detail
     *
     * @return array
     */
    public static function getImpactsOnOrderDetail()
    {
        return [
            'not' => __('No'),
            'disable' => __('Disable edit'),
            'enable' => __('Enable edit'),
        ];
    }

    /**
     * Get Store Operations On Order Detail
     *
     * @return array
     */
    public static function getStoreOperationsOnOrderDetail()
    {
        return Operation::STORE_ORDER_OPERATIONS;
    }
}
