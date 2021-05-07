<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class City
 *
 * @package App
 * @property int $id
 * @property string $name
 * @property double $x_coordinate
 * @property double $y_coordinate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $carriers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CourierTask[] $courierTasks
 * @method static \Illuminate\Database\Eloquent\Builder|\App\City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\City query()
 * @mixin \Eloquent
 */
class City extends Model
{
    protected $fillable = [
        'name',
        'x_coordinate',
        'y_coordinate'
    ];

    /**
     * Get Citie's Orders
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carriers()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get Courier`s tasks
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courierTasks()
    {
        return $this->hasMany(CourierTask::class);
    }
}
