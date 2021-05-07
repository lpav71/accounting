<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\UtmSource
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmSource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmSource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmSource query()
 * @mixin \Eloquent
 * @property int $id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmSource whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmSource whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmSource whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmSource whereUpdatedAt($value)
 */
class UtmSource extends Model
{
    protected $fillable = [
        'name',
    ];
}
