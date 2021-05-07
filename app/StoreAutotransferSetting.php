<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\StoreAutotransferSetting
 *
 * @property int $id
 * @property int $main_store_id
 * @property int $reserve_store_id
 * @property string $name
 * @property int $max_amount
 * @property string|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\StoreAutotransferSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\StoreAutotransferSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\StoreAutotransferSetting query()
 * @mixin \Eloquent
 */
class StoreAutotransferSetting extends Model
{
    protected $fillable = [
        'main_store_id',
        'reserve_store_id',
        'name',
        'max_amount',
        'max_day',
        'min_day',
        'latest_sales_days',
        'settings'
    ];
}
