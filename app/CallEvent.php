<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\CallEvent
 *
 * @property int $id
 * @property int $call_id
 * @property string|null $phone
 * @property string $type
 * @property string $request
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $targetId
 * @property string|null $subscriptionId
 * @property-read \App\Call $call
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent whereCallId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent whereRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CallEvent query()
 * @property string $telephony_name
 */
class CallEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'call_id',
        'phone',
        'type',
        'request',
        'targetId',
        'subscriptionId',
        'telephony_name'
    ];

    /**
     * Get Order's Customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function call()
    {
        return $this->belongsTo('App\Call','id','call_id');
    }
}
