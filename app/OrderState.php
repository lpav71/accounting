<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\OrderState
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $new_order_detail_state_id
 * @property int $check_payment
 * @property string $color
 * @property int $is_sending_external_data
 * @property int $is_successful
 * @property int $is_failure
 * @property bool $check_certificates_number
 * @property bool $is_blocked_edit_order_details
 * @property bool $is_waiting_for_product
 * @property bool $next_auto_closing_status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needOneOrderDetailStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $needOrderDetailStates
 * @property-read \App\OrderDetailState $newOrderDetailState
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderState[] $previousStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereCheckPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereIsBlockedEditOrderDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereIsWaitingForProduct($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereIsSendingExternalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereNewOrderDetailStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderState[] $nextStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState query()
 * @property int $check_carrier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereCheckCarrier($value)
 * @property bool $is_sent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TelegramReportSetting[] $telegramReportSettings
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereIsSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereIsSuccessful($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderState whereIsFailure($value)
 * @property int $is_confirmed
 * @property int $is_new
 * @property int $cdek_not_load
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ExpenseSettings[] $expenseSettings
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Role[] $roles
 * @property int $inactive_order
 * @property int $shipment_available
 */
class OrderState extends Model
{
    protected $casts = [
        'is_blocked_edit_order_details' => 'boolean',
        'is_waiting_for_product' => 'boolean',
        'is_sent' => 'boolean',
        'next_auto_closing_status' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'new_order_detail_state_id',
        'check_payment',
        'color',
        'is_sending_external_data',
        'is_blocked_edit_order_details',
        'is_waiting_for_product',
        'check_carrier',
        'is_sent',
        'is_confirmed',
        'is_successful',
        'is_failure',
        'is_new',
        'next_auto_closing_status',
        'cdek_not_load',
        'inactive_order',
        'shipment_available',
        'check_certificates_number'
    ];

    /**
     * Get State's orders
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orders()
    {
        return $this->belongsToMany('App\Order')->withTimestamps();
    }

    /**
     * Get Order state's previous states
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStates()
    {
        return $this
            ->belongsToMany(
                OrderState::class,
                'order_state_order_state',
                'next_order_state_id',
                'order_state_id');
    }

    /**
     * Get Order state's next states
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nextStates()
    {
        return $this
            ->belongsToMany(
                OrderState::class,
                'order_state_order_state',
            'order_state_id',
                'next_order_state_id');
    }

    /**
     * Get Order state's new Order detail state
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function newOrderDetailState()
    {
        return $this->belongsTo('App\OrderDetailState', 'new_order_detail_state_id');
    }

    /**
     * Get State's need Order detail states
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOrderDetailStates()
    {
        return $this->belongsToMany('App\OrderDetailState', 'need_order_detail_state_order_state')->withTimestamps();
    }

    /**
     * Get State's need minimum one Order detail states
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function needOneOrderDetailStates()
    {
        return $this->belongsToMany('App\OrderDetailState', 'need_one_order_detail_state_order_state')->withTimestamps();
    }

    /**
     * Make sure that format color BEFORE saving it to the database
     *
     * @param $value
     */
    public function setColorAttribute($value)
    {
        if (! is_null($value)) {
            $this->attributes['color'] = strtoupper($value);
        }
    }

    /**
     *  get telegramReportSettings
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function telegramReportSettings()
    {
        return $this->belongsToMany('App\TelegramReportSetting');
    }

    /**
     * Настройки в которых используются статусы заказов
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function expenseSettings()
    {
        return $this->belongsToMany(ExpenseSettings::class, 'expense_settings_order_state_expense_settings', 'order_state_id', 'setting_id');
    }

    /**
     * Роли которые имеют права работать со статусами заказов
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_order_state');
    }

    /**
     *
     * @return bool
     */
    public function isFinalState():bool
    {
        return $this->is_successful || $this->is_failure;
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
    public function inactiveOrder(): bool
    {
        return $this->inactive_order;
    }
}
