<?php

namespace App\Filters;

use App\Task;
use Kyslik\LaravelFilterable\Generic\Filter;
use Carbon\Carbon;

/**
 * Фильтры задач
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class TaskFilter extends Filter
{
    /**
     * Конечные свойства модели, по которым может идти сортировка
     *
     * @var array
     */
    protected $filterables = ['id'];

    /**
     * Карта фильтров
     *
     * @return array
     */
    public function filterMap(): array
    {
        return [
            'id' => ['id'],
            'state' => ['state'],
            'customer' => ['customer'],
            'orderid' => ['orderid'],
            'date' => ['date'],
            'performer' => ['performer'],
            'author' => ['author'],
            'deadlinedate' => ['deadlinedate'],
            'priority' => ['priority'],
            'type' => ['type'],
        ];
    }

    /**
     * Фильтр по id
     *
     * @param int $taskId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function id($taskId = 0)
    {
        return $taskId ? $this->builder->where('id', $taskId) : $this->builder;
    }

    /**
     * Фильтр по статусу
     *
     * @param int $stateId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function state($stateId = 0)
    {
        if($stateId) {
            //time of last task state's creating
            $latestStates = \DB::table('task_task_state')
                ->select('task_id', \DB::raw('MAX(created_at) as last_state_created_at'))
                ->groupBy('task_id');

            //tasks with need states
            $taskIds = \DB::table('task_task_state')
                ->joinSub($latestStates, 'latest_states', function ($join) {
                    $join->on('task_task_state.task_id', '=', 'latest_states.task_id')
                        ->on('task_task_state.created_at', '=', 'latest_states.last_state_created_at');
                })
                ->where('task_task_state.task_state_id', $stateId)
                ->pluck('task_task_state.task_id');
            return $this->builder->whereIn('id', $taskIds);
        } else {
            return $this->builder;
        }
    }

    /**
     * Фильтр по автору
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function author($userId = 0)
    {
        return $userId ? $this->builder->whereIn(
            'id',
            Task::where('author_user_id', $userId)->pluck('id')
        ) : $this->builder;
    }

    /**
     * Фильтр по ответственному
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function performer($userId = 0)
    {
        return $userId ? $this->builder->whereIn(
            'id',
            Task::where('performer_user_id', $userId)->pluck('id')
        ) : $this->builder;
    }

    /**
     * Фильтр по клиенту
     *
     * @param int $customerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function customer($customerId = 0)
    {
        return $customerId ? $this->builder->whereIn(
            'id',
            Task::where('customer_id', $customerId)->pluck('id')
        ) : $this->builder;
    }

    /**
     * Фильтр по заказу
     *
     * @param int $orderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function orderid($orderId = 0)
    {
        return $orderId ? $this->builder->whereIn(
            'id',
            Task::where('order_id', $orderId)->pluck('id')
        ) : $this->builder;
    }

    /**
     * Фильтр по дате создания
     *
     * @param int $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function date($date = 0)
    {
        return $date ? $this->builder->where(
            'created_at',
            '>=',
            Carbon::createFromFormat('d-m-Y', $date)->setTime(0, 0, 0, 0)->toDateTimeString()
        )->where(
            'created_at',
            '<',
            Carbon::createFromFormat('d-m-Y', $date)->addDay()->setTime(0, 0, 0, 0)->toDateTimeString()
        ) : $this->builder;
    }

    /**
     * Фильтр по дате срока
     *
     * @param int $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function deadlinedate($date = 0)
    {
        return $date ? $this->builder->where(
            'deadline_date',
            '>=',
            Carbon::createFromFormat('d-m-Y', $date)->setTime(0, 0, 0, 0)->toDateTimeString()
        )->where(
            'deadline_date',
            '<',
            Carbon::createFromFormat('d-m-Y', $date)->addDay()->setTime(0, 0, 0, 0)->toDateTimeString()
        ) : $this->builder;
    }

    /**
     * Фильтр по приоритету
     *
     * @param int $priorityId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function priority($priorityId = 0)
    {
        return $priorityId ? $this->builder->whereIn(
            'id',
            Task::where('task_priority_id', $priorityId)->pluck('id')
        ) : $this->builder;
    }

    /**
     * Фильтр по типу
     *
     * @param int $typeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function type($typeId = 0)
    {
        return $typeId ? $this->builder->whereIn(
            'id',
            Task::where('task_type_id', $typeId)->pluck('id')
        ) : $this->builder;
    }
}
