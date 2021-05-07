<?php

namespace App;

use Carbon\Carbon;
use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Model;

/**
 * App\RoutePoint
 *
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $pointObject
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\RoutePointState[] $states
 * @mixin \Eloquent
 * @property int $id
 * @property int $route_list_id
 * @property string $point_object_type
 * @property int $point_object_id
 * @property bool $is_point_object_attached
 * @property string|null $delivery_post_index
 * @property string|null $delivery_city
 * @property string|null $delivery_address
 * @property string|null $delivery_flat
 * @property string|null $delivery_comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\RouteList $routeList
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereDeliveryAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereDeliveryCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereDeliveryComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereDeliveryEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereDeliveryFlat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereDeliveryPostIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereDeliveryStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereIsPointObjectAttached($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint wherePointObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint wherePointObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereRouteListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePoint query()
 * @property string $delivery_end_time
 * @property string $delivery_start_time
 */
class RoutePoint extends Model
{
    use HasBelongsToManyEvents;

    const POINT_OBJECT_TYPES = [
        Order::class,
        ProductReturn::class,
        ProductExchange::class,
        CourierTask::class,
    ];

    protected $observables = [
        'belongsToManyAttaching',
    ];

    protected $casts = [
        'point_object_type' => 'string',
        'is_point_object_attached' => 'boolean',
    ];

    protected $fillable = [
        'route_list_id',
        'point_object_type',
        'point_object_id',
        'is_point_object_attached',
        'delivery_post_index',
        'delivery_city',
        'delivery_address',
        'delivery_flat',
        'delivery_comment',
    ];

    /**
     * Атрибуты, которым можно присвоить значения только при создании объекта
     *
     * @var array
     */
    protected $creatableOnly = [
        'point_object_type',
        'point_object_id',
    ];

    /**
     * Получение объекта исполнения маршрутной точки
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function pointObject()
    {
        return $this->morphTo();
    }


    /**
     * Маршрутный лист
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function routeList()
    {
        return $this->belongsTo(RouteList::class);
    }

    /**
     * Статусы
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function states()
    {
        return $this
            ->belongsToMany(RoutePointState::class)
            ->withPivot('created_at')
            ->orderBy('route_point_route_point_state.id', 'desc');
    }

    /**
     * Текущий статус
     *
     * @return RoutePointState|null
     */
    public function currentState()
    {
        return $this->states()->first();
    }

    /**
     * Последующие статусы
     *
     * @return \Illuminate\Support\Collection
     */
    public function nextStates()
    {
        return $this->currentState() ? $this->currentState()->nextStates()->get() : collect([]);
    }

    /**
     * Форматирование расчетного начального времени доставки извлеченного из БД
     *
     * @param $value
     * @return string
     */
    public function getDeliveryStartTimeAttribute($value)
    {
        return is_null($value) ? $value : Carbon::createFromFormat('H:i:s', $value)->format('H:i');
    }

    /**
     * Форматирование расчетного начального времени доставки для сохранения в БД
     *
     * @param $value
     */
    public function setDeliveryStartTimeAttribute($value)
    {
        $this->attributes['delivery_start_time'] =
            is_null($value) ? $value : Carbon::createFromFormat('H:i', $value)->format('H:i:s');
    }

    /**
     * Форматирование расчетного конечного времени доставки извлеченного из БД
     *
     * @param $value
     * @return string
     */
    public function getDeliveryEndTimeAttribute($value)
    {
        return is_null($value) ? $value : Carbon::createFromFormat('H:i:s', $value)->format('H:i');
    }

    /**
     * Форматирование расчетного конечного времени доставки для сохранения в БД
     *
     * @param $value
     */
    public function setDeliveryEndTimeAttribute($value)
    {
        $this->attributes['delivery_end_time'] =
            is_null($value) ? $value : Carbon::createFromFormat('H:i', $value)->format('H:i:s');
    }

    /**
     * Атрибуты, которым можно присвоить значения только при создании объект
     *
     * @return array
     */
    public function getCreatableOnly()
    {
        return $this->creatableOnly;
    }

    /**
     * Получение адреса доставки одной строкой
     *
     * @return string
     */
    public function getFullAddress()
    {
        return collect(
            [
                'delivery_post_index' => $this->delivery_post_index,
                'delivery_city' => $this->delivery_city,
                'delivery_address' => $this->delivery_address,
                'delivery_flat' => $this->delivery_flat,
                'delivery_comment' => $this->delivery_comment,
            ]
        )
            ->filter(
                function ($item) {
                    return !is_null($item) && $item !== '';
                }
            )
            ->map(
                function ($item, $key) {
                    return $key == 'delivery_flat' ? __('fl').' '.$item : $item;
                }
            )
            ->implode(', ');
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
                'delivery_post_index' => $this->delivery_post_index,
                'delivery_city' => $this->delivery_city,
                'delivery_address' => $this->delivery_address,
                'delivery_flat' => $this->delivery_flat,
            ]
        )->filter(
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        )->map(
            function ($item, $key) {
                return $key == 'delivery_flat' ? __('fl').' '.$item : $item;
            }
        )->implode(', ');
    }
}
