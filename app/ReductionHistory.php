<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ReductionHistory
 *
 * @property int $id
 * @property int $channel_id
 * @property string $text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ReductionHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ReductionHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ReductionHistory query()
 * @mixin \Eloquent
 */
class ReductionHistory extends Model
{
    protected $fillable = [
        'channel_id',
        'text'
    ];
}
