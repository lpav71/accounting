<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\PickupPoint
 *
 * @property int $id
 * @property string $postal_index
 * @property string $code
 * @property string $name
 * @property string $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint wherePostalIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PickupPoint query()
 */
class PickupPoint extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'postal_index',
        'code',
        'name',
        'address',
    ];
}
