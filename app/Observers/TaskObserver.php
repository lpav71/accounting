<?php

namespace App\Observers;

use App\Jobs\ReassignTasksPerformers;
use App\Order;
use App\Services\SecurityService\SecurityService;
use App\Task;
use Auth;

/**
 * Наблюдатель задачи
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class TaskObserver
{
    /**
     * Обработка события 'creating'
     *
     * @param Task $task
     */
    public function creating(Task $task)
    {
        $task->author_user_id = Auth::id();
    }

    /**
     * Обработка события 'created'
     *
     * @param Task $task
     */
    public function created(Task $task)
    {

        if ($task->order && $task->order instanceof Order && $task->order->exists) {
            $changes = $this->getDirtyAttributesForComment($task);

            $task->order->comments()->create(
                [
                    'comment' => __('Task created')." #task-{$task->id}"
                        .($changes ? ':<br>'.implode("<br>", $changes) : '.'),
                    'user_id' => \Auth::id() ?: null,
                ]
            );
        }

        $this->reassignTask($task);
    }

    /**
     * Обработка события 'updated'
     *
     * @param Task $task
     */
    public function updated(Task $task)
    {
        if ($task->order && $task->order instanceof Order && $task->order->exists) {
            $changes = $this->getDirtyAttributesForComment($task);

            if ($changes) {
                $task->order->comments()->create(
                    [
                        'comment' => __('Task updated')." #task-{$task->id}: ".implode('<br>', $changes),
                        'user_id' => \Auth::id() ?: null,
                    ]
                );
            }
        }

        $this->reassignTask($task);
        $task->putChanges();
    }

    /**
     * Обработка события 'belongsToManyAttaching'
     *
     * @param string $relation
     * @param Task $task
     * @param array $ids
     * @param array $attributes
     * @return bool
     */
    public function belongsToManyAttaching($relation, Task $task, array $ids, array $attributes)
    {
        $result = true;

        switch ($relation) {
            case 'states':

                // Указание текущего пользователя автором статуса
                if (count($ids) <= 1) {

                    $attributes['user_id'] = $attributes['user_id'] ?? null;

                } else {
                    foreach ($ids as $id) {
                        $attributes[$id]['user_id'] = $attributes[$id]['user_id'] ?? null;
                    }
                }

                if (!$result) {
                    $task->states()->attach($ids, $attributes);
                }

                break;
        }

        return $result;
    }

    /**
     * Обработка события 'belongsToManyAttached'
     *
     * @param string $relation
     * @param Task $task
     * @param array $ids
     */
    public function belongsToManyAttached($relation, Task $task, array $ids)
    {
        if($task->currentState()->is_closed && $task->check_related_order) {
            $service = new SecurityService();
            $service->checkTaskCloseOrder($task);
        }
        if ($task->order && $task->order instanceof Order && $task->order->exists) {
            switch ($relation) {
                case 'states':

                    $task->order->comments()->create(
                        [
                            'comment' => __('State of task')." #task-{$task->id}: ".$task->currentState()->name,
                            'user_id' => \Auth::id() ?: null,
                        ]
                    );

                    break;
            }
        }
    }

    /**
     * Подготовка массива измененных аттрибутов для комментария.
     *
     * @param Task $task
     * @return array
     */
    protected function getDirtyAttributesForComment(Task $task)
    {
        $changes = [];

        $names = [
            'name' => __('Name'),
            'description' => __('Description'),
            'deadline_date' => __('Deadline date'),
            'deadline_time' => __('Deadline time'),
        ];

        foreach ($task->getDirty() as $name => $value) {

            if (in_array($name, array_keys($names))) {
                if ($name == 'deadline_date' && substr($task->getOriginal($name), 0, 10) == $value) {
                    continue;
                }
                $changes[] = "{$names[$name]}: '{$task->getOriginal($name)}' -> '{$value}'";
            }

        }

        return $changes;
    }

    /**
     * Постановка задачи на переназначение отвественных при необходимости
     *
     * @param Task $task
     */
    protected function reassignTask(Task $task): void
    {
        $needReassign = false;

        foreach ($task->getDirty() as $name => $value) {
            if (in_array($name, ['deadline_date', 'deadline_time', 'task_type_id'])) {
                $needReassign = true;
                break;
            }
        }

        if ($needReassign) {
            ReassignTasksPerformers::dispatch($task);
        }

    }
}
