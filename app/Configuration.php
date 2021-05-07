<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Configuration
 *
 * @property int $id
 * @property string $name
 * @property string $values
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Configuration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Configuration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Configuration whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Configuration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Configuration whereValues($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Configuration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Configuration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Configuration query()
 */
class Configuration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'values'
    ];
}
