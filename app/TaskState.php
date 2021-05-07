<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TaskState
 *
 * @property int $id
 * @property string $name
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $is_closed
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TaskState[] $previousStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Task[] $tasks
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState whereIsClosed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState query()
 * @property int $is_new
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TelegramReportSetting[] $telegramReportSettings
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskState whereIsNew($value)
 */
class TaskState extends Model
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $fillable = [
        'name',
        'color',
        'is_closed',
        'is_new'
    ];

    /**
     *  get tasks
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tasks()
    {
        return $this->belongsToMany('App\Task')->withTimestamps();
    }

    /**
     *  get previousStates
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStates()
    {
        return $this->belongsToMany('App\TaskState', 'task_state_task_state', 'next_task_state_id', 'task_state_id');
    }

    /**
     * get telegramReportSettings
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function telegramReportSettings()
    {
        return $this->belongsToMany('App\TelegramReportSetting');
    }
}
