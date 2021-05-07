<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\FastMessageLog
 *
 * @property int $id
 * @property int $user_id
 * @property string $destination
 * @property string $message
 * @property string $type
 * @property string $sender
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FastMessageLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FastMessageLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FastMessageLog query()
 * @mixin \Eloquent
 */
class FastMessageLog extends Model
{

    protected $fillable = [
        'user_id',
        'message',
        'destination',
        'type',
        'sender'
    ];


}
