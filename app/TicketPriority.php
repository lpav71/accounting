<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketPriority
 *
 * @property int $id
 * @property string $name
 * @property int $rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketPriority newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketPriority newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketPriority query()
 * @mixin \Eloquent
 * @property int $is_default
 */
class TicketPriority extends Model
{
    /**
     * fillable attributes
     *
     * @var array
     */
    protected $fillable = ['name','rate','is_default'];

}
