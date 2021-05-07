<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\CarrierType
 *
 * @property int $id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CarrierType extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'name'
    ];
}
