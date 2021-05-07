<?php

namespace App;

use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Задача для курьера
 * Class CourierTask
 *
 * @package App
 * @property int $id
 * @property string $date
 * @property string $address
 * @property string $city
 * @property string $comment
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int|null $city_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CourierTaskState[] $states
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CourierTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CourierTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CourierTask query()
 * @mixin \Eloquent
 */
class CourierTask extends Model
{
    use HasBelongsToManyEvents;

    protected $observables = [
        'belongsToManyAttaching',
        'belongsToManyAttached',
    ];

    protected $fillable = [
        'date',
        'address',
        'comment',
        'start_time',
        'end_time',
        'city',
        'city_id'
    ];

    /**
     * Маршрутные точки
     *
     * @return Collection
     */
    public function routePoints()
    {
        return RoutePoint::where('point_object_type', self::class)
            ->where('point_object_id', $this->id)
            ->get();
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Текущий маршрутный лист
     *
     * @return RouteList|null
     */
    public function routeList()
    {
        /**
         * @var RoutePoint $routePoint
         */
        $routePoint = $this->routePoints()->where('is_point_object_attached', 1)->first();

        return (is_null($routePoint) ? null : $routePoint->routeList);
    }

    /**
     * Статусы
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function states()
    {
        return $this->
            belongsToMany(CourierTaskState::class)
            ->withPivot('created_at')
            ->orderBy('courier_task_courier_task_state.id', 'desc');
    }

    /**
     * Текущий статус
     *
     * @return mixed
     */
    public function currentState()
    {
        return $this->states()->first();
    }

    /**
     * Последующие статусы
     *
     * @return Collection
     */
    public function nextStates()
    {
        return $this->currentState() ? $this->currentState()->nextStates()->get() : collect([]);
    }

    /**
     * Адрес для карты
     *
     * @return string
     */
    public function getMapDeliveryAddress()
    {
        return collect(
            [
                'city' => $this->city,
                'address' => $this->address,
            ]
        )->filter(
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        )->implode(', ');
    }
}
