<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Weekday
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Weekday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Weekday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Weekday query()
 * @mixin \Eloquent
 */
class Weekday extends Model
{
    protected $fillable = ['name'];

    /**
     * get translated array for select/option
     * @return array
     */
    static function getSelect():array {
        $weekdays = Weekday::pluck('name', 'id')->toArray();
        foreach ($weekdays as &$weekday) {
            $weekday = __( $weekday);
        }
        return $weekdays;
    }
}
