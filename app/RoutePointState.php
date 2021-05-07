<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\RoutePointState
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\RoutePointState[] $previousStates
 * @property-write mixed $color
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property int|null $new_order_state_id
 * @property int|null $new_product_return_state_id
 * @property int|null $new_product_exchange_state_id
 * @property bool $is_detach_point_object
 * @property bool $is_successful
 * @property bool $is_failure
 * @property bool $is_new
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereIsDetachPointObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereNewOrderStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereNewProductExchangeStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereNewProductReturnStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\RoutePointState[] $nextStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState query()
 * @property bool $is_attach_detached_point_object
 * @property bool $is_need_comment_to_point_object
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereIsAttachDetachedPointObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereIsNeedCommentToPointObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereIsNew($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereIsFailure($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RoutePointState whereIsSuccessful($value)
 */
class RoutePointState extends Model
{
    protected $casts = [
        'is_detach_point_object' => 'boolean',
        'is_attach_detached_point_object' => 'boolean',
        'is_need_comment_to_point_object' => 'boolean',
        'is_successful' => 'boolean',
        'is_failure' => 'boolean',
        'is_new' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'color',
        'new_order_state_id',
        'new_product_return_state_id',
        'new_product_exchange_state_id',
        'is_detach_point_object',
        'is_attach_detached_point_object',
        'is_need_comment_to_point_object',
        'is_successful',
        'is_failure',
        'is_new',
    ];

    /**
     * Возможные предыдущие статусы маршрутных точек
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStates()
    {
        return $this->belongsToMany(
            RoutePointState::class,
            'route_point_state_route_point_state',
            'route_point_state_id',
            'previous_state_id'
        );
    }

    /**
     * Возможные следующие статусы маршрутных точек
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nextStates()
    {
        return $this
            ->belongsToMany(
                RoutePointState::class,
                'route_point_state_route_point_state',
                'previous_state_id',
                'route_point_state_id');
    }

    /**
     * Форматирование цвета для сохранения в БД
     *
     * @param $value
     */
    public function setColorAttribute($value)
    {
        $this->attributes['color'] = is_null($value) ? $value : strtoupper($value);
    }
}
