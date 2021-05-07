<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\PhoneEvent
 *
 * @property int $id
 * @property string $phone
 * @property string $type
 * @property string $request
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent whereRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PhoneEvent query()
 * @property string $telephony_name
 */
class PhoneEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'phone',
        'request',
        'telephony_name'
    ];
}
