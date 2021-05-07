<?php

namespace App;

use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Kyslik\LaravelFilterable\Filterable;
use Kyslik\ColumnSortable\Sortable;
use App\Traits\ModelLogger;
/**
 * Задача пользователя
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $customer_id
 * @property int|null $order_id
 * @property int|null $author_user_id
 * @property int|null $performer_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deadline_date
 * @property string|null $deadline_time
 * @property int $task_priority_id
 * @property bool $check_related_order
 * @property \Illuminate\Support\Carbon|null $last_open_time
 * @property int $task_type_id
 * @property-read \App\User|null $author
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TaskComment[] $comments
 * @property-read \App\Customer|null $customer
 * @property-read \App\OverdueTasksAlerts|null $overdueAlerts
 * @property-read \App\Order|null $order
 * @property-read \App\User|null $performer
 * @property-read \App\TaskPriority $priority
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TaskState[] $states
 * @property-read \App\TaskType $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Task filter(\Kyslik\LaravelFilterable\FilterContract $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Task query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Task sortable($defaultParameters = null)
 * @mixin \Eloquent
 */
class Task extends Model
{
    use Filterable, Sortable, HasBelongsToManyEvents, ModelLogger;

    protected $observables = [
        'belongsToManyAttaching',
        'belongsToManyAttached',
    ];

    protected $casts = [
        'last_open_time' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'description',
        'customer_id',
        'order_id',
        'performer_user_id',
        'deadline_date',
        'deadline_time',
        'task_priority_id',
        'task_type_id',
        'last_open_time',
        'check_related_order'
    ];

    public $sortable = [
        'id',
        'created_at',
    ];

    /**
     * Клиент
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    /**
     * Заказ
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class)->withDefault();
    }

    /**
     * Автор
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /**
     * Исполнитель
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performer_user_id')->withDefault();
    }

    /**
     * Приоритет
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priority()
    {
        return $this->belongsTo(TaskPriority::class, 'task_priority_id')->withDefault();
    }

    /**
     * Тип
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(TaskType::class, 'task_type_id')->withDefault();
    }

    /**
     * Статусы
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function states()
    {
        return $this
            ->belongsToMany(TaskState::class)
            ->withPivot('created_at')
            ->orderBy('task_task_state.id', 'desc');
    }

    /**
     * Комментарии
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }
    
  /**
     * Просроченные задачи
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function overdueTasks()
    {
        return $this->belongsToMany(OverdueTask::class, 'task_overdue_tasks', 'task_id' , 'trashold_id')
        ->orderBy('task_overdue_tasks.id', 'desc')
        ->withTimestamps();
    }
    /**
     * Текущий статус
     *
     * @return TaskState|null
     */
    public function currentState()
    {
        return $this->states()->first();
    }


    /**
     * Cтатус в определенное время
     *
     * @return TaskState|null
     */
    public function stateOnDate(Carbon $time)
    {
        return $this->states()->wherePivot('created_at', '<', $time->toDateTimeString())->first();
    }

    /**Уведомления о просроченных задачах
     *
     * @return bool
     */
    public function isClosed()
    {
        return (bool)!is_null($this->currentState()) && $this->currentState()->is_closed;
    }

    /**
     * Форматирование даты исполнения для сохранения в БД
     *
     * @param $value
     */
    public function setDeadlineDateAttribute($value)
    {
        $this->attributes['deadline_date'] = is_null($value) ?
            null :
            Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
    }

    /**
     * Форматирование даты исполнения извлеченной из БД
     *
     * @param $value
     * @return string|null
     */
    public function getDeadlineDateAttribute($value)
    {
        return is_null($value) ? null : Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('d-m-Y');
    }

    /**
     * Освобожденные задачи
     *
     * Возвращает освобожденные задачи, с момента последнего открытия которых прошло определенное количество минут
     * Если $deadlineDate = null - возвращает все освобожденные задачи, если нет - задачи с датой исполнения меньшей
     * или равной указанной или с неустановленной датой исполнения.
     *
     * @param Carbon|null $deadlineDate
     * @param int $minutes
     * @param bool $isOpenedOnly
     * @return Task[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getReleasedTasks(?Carbon $deadlineDate = null, int $minutes = 10, bool $isOpenedOnly = true)
    {
        $query = Task::query()
            ->where(
                function (Builder $query) use ($minutes) {
                    return $query
                        ->where('last_open_time', '<', Carbon::now()->subMinutes($minutes))
                        ->orWhereNull('last_open_time');
                }
            );

        if (!is_null($deadlineDate)) {
            $query = $query->where(
                function (Builder $query) use ($deadlineDate) {
                    return $query
                        ->where('deadline_date', '<=', $deadlineDate)
                        ->orWhereNull('deadline_date');
                }
            );
        }

        if ($isOpenedOnly) {
            $query = $query->leftJoinSub(
                function (\Illuminate\Database\Query\Builder $query) {
                    return $query
                        ->select(['task_id', 'task_states.is_closed'])
                        ->from('task_task_state')
                        ->leftJoin('task_states', 'task_state_id', '=', 'task_states.id')
                        ->where('task_states.is_closed', '1');
                },
                'a',
                'id',
                '=',
                'a.task_id'
            )
                ->whereNull('is_closed');
        }

        return $query->get();
    }

    /**
     * Актуальные задачи
     *
     * @param User|null $performer
     * @return Task[]|\Illuminate\Support\Collection
     */
    public static function getActualTasks(?User $performer = null)
    {
        $query = Task::query()
            ->select('tasks.*')
            ->join('task_priorities', 'tasks.task_priority_id', '=', 'task_priorities.id')
            ->where(
                'tasks.deadline_date',
                '<',
                Carbon::now()->addDay()->setTime(0, 0, 0, 0)->toDateTimeString()
            )
            ->leftJoinSub(
                function (\Illuminate\Database\Query\Builder $query) {
                    return $query
                        ->selectRaw('`task_task_state`.*')
                        ->from('task_task_state')
                        ->leftJoinSub(
                            function (\Illuminate\Database\Query\Builder $query) {
                                return $query
                                    ->selectRaw('task_id, MAX(id) as id')
                                    ->from('task_task_state')
                                    ->groupBy('task_id');
                            },
                            'task_task_state_j',
                            'task_task_state_j.id',
                            '=',
                            'task_task_state.id'
                        )
                        ->whereNotNull('task_task_state_j.task_id');
                },
                'task_task_state',
                'tasks.id',
                '=',
                'task_task_state.task_id'
            )
            ->leftJoin('task_states', 'task_states.id', '=', 'task_task_state.task_state_id')
            ->where('task_states.is_closed', 0)
            ->orderByDesc('tasks.deadline_date')
            ->orderByDesc('task_priorities.rate')
            ->orderByDesc('tasks.deadline_time');

        if (!is_null($performer)) {
            $query = $query->where('tasks.performer_user_id', $performer->id);
        }

        return $query;
    }


    /**
     * Просроченные актуальные задачи
     *
     * @param User|null $performer
     * @return Task[]|\Illuminate\Support\Collection
     */
    public static function getExpiredTasks(?User $performer = null)
    {
        $query = Task::select('tasks.*')
            ->join('task_priorities', 'tasks.task_priority_id', '=', 'task_priorities.id')
            ->where(
                'tasks.deadline_date',
                '>=',
                Carbon::now()->setTime(0, 0, 0, 0)->toDateTimeString()
            )
            ->where(
                'tasks.deadline_date',
                '<',
                Carbon::now()->addDay()->setTime(0, 0, 0, 0)->toDateTimeString()
            )
            ->where(
                'tasks.deadline_time',
                '<',
                Carbon::now()->format('H:i')
            )
            ->where(function ($q) {
                $q->where('task_priorities.is_very_urgent', 1);
            })->leftJoinSub(
                function (\Illuminate\Database\Query\Builder $query) {
                    return $query
                        ->selectRaw('`task_task_state`.*')
                        ->from('task_task_state')
                        ->leftJoinSub(
                            function (\Illuminate\Database\Query\Builder $query) {
                                return $query
                                    ->selectRaw('task_id, MAX(id) as id')
                                    ->from('task_task_state')
                                    ->groupBy('task_id');
                            },
                            'task_task_state_j',
                            'task_task_state_j.id',
                            '=',
                            'task_task_state.id'
                        )
                        ->whereNotNull('task_task_state_j.task_id');
                },
                'task_task_state',
                'tasks.id',
                '=',
                'task_task_state.task_id'
            )
            ->leftJoin('task_states', 'task_states.id', '=', 'task_task_state.task_state_id')
            ->where('task_states.is_closed', 0)
            ->orderByDesc('tasks.deadline_date')
            ->orderByDesc('task_priorities.rate')
            ->orderByDesc('tasks.deadline_time');

        if (!is_null($performer)) {
            $query = $query->where('tasks.performer_user_id', $performer->id);
        }

        return $query;
    }
}
