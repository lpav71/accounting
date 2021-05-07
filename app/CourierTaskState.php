<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Статусы задач для курьеров
 * Class CourierTaskStates
 *
 * @package App
 * @property int $id
 * @property string $name
 * @property bool $is_successful
 * @property bool $is_courier_state
 * @property bool $is_failure
 * @property bool $is_new
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CourierTask[] $courierTasks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CourierTaskState[] $nextStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CourierTaskState[] $previousStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CourierTaskState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CourierTaskState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CourierTaskState query()
 * @mixin \Eloquent
 */
class CourierTaskState extends Model
{
    protected $fillable = [
        'name',
        'is_successful',
        'is_failure',
        'is_new',
        'is_courier_state'
    ];

    protected $casts = [
        'is_successful' => 'boolean',
        'is_failure' => 'boolean',
        'is_new' => 'boolean',
        'is_courier_state' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function courierTasks()
    {
        return $this->belongsToMany(CourierTask::class)->withTimestamps();
    }

    /**
     * Возможные предыдущие статусы задач для курьера
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStates()
    {
        return $this->belongsToMany(
            CourierTaskState::class,
            'courier_task_state_courier_task_state',
            'courier_task_state_id',
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
                CourierTaskState::class,
                'courier_task_state_courier_task_state',
                'previous_state_id',
                'courier_task_state_id');
    }
}
