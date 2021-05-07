<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Carrier
 *
 * @property int $id
 * @property string $name
 * @property bool $is_internal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $city_id
 * @property string $config
 * @property bool $close_order_task
 * @property int $carrier_group_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier whereIsInternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier query()
 * @property int $carrier_type_id
 * @property-read \App\CarrierType $carrier_type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier whereCarrierTypeId($value)
 * @property string $url_link
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Carrier whereUrlLink($value)
 * @property-read \App\City|null $city
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ExpenseSettings[] $expenseSettings
 * @property int $self_shipping
 * @property-read \App\CarrierGroup|null $carrierGroup
 */
class Carrier extends Model
{
    protected $casts = [
        'is_internal' => 'boolean',
    ];
    protected $fillable = [
        'name',
        'is_internal',
        'config',
        'carrier_type_id',
        'url_link',
        'city_id',
        'close_order_task',
        'self_shipping',
        'carrier_group_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get Carrier's Orders
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany('App\Order');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function carrier_type(){
        return $this->belongsTo('App\CarrierType');
    }

    /**
     * Make sure that format config BEFORE saving it to the database
     *
     * @param $value
     */
    public function setConfigAttribute($value)
    {
        $config = collect(explode("\r\n", $value))->reduce(function (Collection $acc, $row) {
            $configRow = collect(explode(':', $row));

            return $configRow->count() == 2 ? $acc->merge([$configRow->first() => $configRow->last()]) : $acc;
        }, collect());
        $this->attributes['config'] = $config->count() ? $config->toJson() : null;
    }

    /**
     * Make sure that format config when retrieved from the database
     *
     * @param $value
     * @return string
     */
    public function getConfigAttribute($value)
    {
        return is_null($value) ? '' : collect(json_decode($value))->map(function ($item, $key) {
            return $key.':'.$item;
        })->implode("\r\n");
    }


    /**
     * get Config vars collection
     *
     * @return \Illuminate\Support\Collection
     */
    public function getConfigVars()
    {
        return collect(explode("\r\n", $this->config))->reduce(function (Collection $acc, $row) {
            $configRow = collect(explode(':', $row));

            return $configRow->count() == 2 ? $acc->merge([$configRow->first() => $configRow->last()]) : $acc;
        }, collect());
    }

    /**
     * Настройки в которых используются служы доставки
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function expenseSettings()
    {
        return $this->belongsToMany(ExpenseSettings::class, 'expense_settings_carriers_expense_settings', 'carrier_id', 'setting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function carrierGroup()
    {
        return $this->belongsTo(CarrierGroup::class);
    }
}
