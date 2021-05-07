<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketTheme
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketTheme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketTheme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketTheme query()
 * @mixin \Eloquent
 */
class TicketTheme extends Model
{
    /**
     * fillable attributes
     *
     * @var array
     */
    protected $fillable = ['name','is_hidden'];
}
