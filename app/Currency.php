<?php

namespace App;

use App\Traits\Operable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Currency
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property float $currency_rate
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereCurrencyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency query()
 * @property string $iso_code
 * @property int|null $is_default
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereIsoCode($value)
 */
class Currency extends Model
{
    use Operable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'currency_rate','iso_code','is_default'];
}
