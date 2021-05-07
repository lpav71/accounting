<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \App\Channel;

/**
 * App\FastMessageTemplate
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $message
 * @property int $is_track_notification
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Channel[] $channels
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FastMessageTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FastMessageTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FastMessageTemplate query()
 * @mixin \Eloquent
 */
class FastMessageTemplate extends Model
{

    protected $fillable = [
        'name',
        'type',
        'message',
        'is_track_notification'
    ];

    /**
     * Позиции заказа
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channels()
    {
        return $this->belongsToMany(Channel::class);
    }
}
