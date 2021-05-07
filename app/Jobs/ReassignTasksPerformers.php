<?php

namespace App\Jobs;

use App\CourierTask;
use App\CourierTaskState;
use App\OrderComment;
use App\OrderDetailState;
use App\OrderState;
use App\ProductExchangeState;
use App\Task;
use App\User;
use App\UserWorkTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;
use Log;
use Tightenco\Parental\Tests\Models\Car;

/**
 * Задача переназначения исполнителей задач
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class ReassignTasksPerformers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Выполнение задачи переназначения для конкретной задачи
     *
     * @var Task
     */
    protected $task;

    public function __construct(?Task $task = null)
    {
        $this->task = $task;
    }

    /**
     * Запуск задачи переназначения исполнителей задач на сегодня
     *
     * @return void
     */
    public function handle()
    {
        $deadlineDate = Carbon::now()->setTime(23, 59, 59, 999);

        $releasedTasks = Task::getReleasedTasks($deadlineDate);

        $tasks = !is_null($this->task) ? collect([$this->task]) : $releasedTasks;

        foreach ($tasks as $task) {
            $this->reassignTask($task, $deadlineDate, $releasedTasks);
        }
    }

    /**
     * Перехват ошибки задачи
     *
     * @param \Throwable $exception
     */
    public function fail($exception = null)
    {
        Log::channel('single')->error($exception->getMessage(), $exception->getTrace());
    }

    /**
     * Переназначение задачи исполнителю
     *
     * @param Task       $task
     * @param Carbon     $deadlineDate
     * @param Collection $releasedTasks
     */
    protected function reassignTask(Task $task, Carbon $deadlineDate, Collection $releasedTasks)
    {
        $task = $task->fresh();

        if (!is_null($task->deadline_date)) {
            $taskDeadlineDate = Carbon::createFromFormat('d-m-Y', $task->deadline_date);

            if ($taskDeadlineDate->getTimestamp() > $deadlineDate->getTimestamp()) {
                return;
            }
        }

        if (in_array($task->performer->id, $task->type->balancerDisabledUsers->pluck('id')->toArray())) {
            return;
        }

        if ($releasedTasks->where('id', $task->id)->isEmpty()) {
            !$task->isClosed() && ReassignTasksPerformers::dispatch($task);

            return;
        }

        foreach ($task->type->balancerPriorityUsers as $user) {
            if ($this->assignTaskToUser($task, $user)) {
                return;
            }
        }

        foreach ($task->type->balancerPriorityRoles as $role) {
            foreach ($role->users as $user) {
                if ($this->assignTaskToUser($task, $user)) {
                    return;
                }
            }
        }

        if (!is_null($task->author) && $this->assignTaskToUser($task, $task->author)) {
            return;
        }

        $order = $task->order;

        if (!is_null($order)) {
            /**
             * @var OrderComment $comment
             */
            $comment = $order
                ->comments()
                ->where('comment', 'like', "%#task-{$task->id}:%")
                ->orderByDesc('created_at')
                ->first();

            if (!is_null($comment) && $this->assignTaskToUser($task, $comment->author)) {
                return;
            }
        }

        if (!is_null($order)) {

            $lastOrderReservationState = $order
                ->states()
                ->withPivot('user_id')
                ->get()
                ->filter(
                    function (OrderState $orderState) {
                        return $orderState->newOrderDetailState instanceof OrderDetailState && $orderState->newOrderDetailState->store_operation === 'CR';
                    }
                )
                ->first();

            $userId = !is_null($lastOrderReservationState) ? $lastOrderReservationState->getOriginal(
                'pivot_user_id'
            ) : null;

            $user = !is_null($userId) ? User::find($userId) : null;

            if (!is_null($user) && $this->assignTaskToUser($task, $user)) {
                return;
            }
        }

        $this->assignTask($task);
    }

    /**
     * Назначает задачу исполнителю
     * Фасад для метода assignTask с более строгим интерфейсом
     *
     * @param Task $task
     * @param User $user
     *
     * @return bool
     */
    protected function assignTaskToUser(Task $task, User $user): bool
    {
        return $this->assignTask($task, $user);
    }

    /**
     * Назначает задачу исполнителю
     * При успешном назначении возвращает - true, в противном случае - false
     *
     * @param Task      $task
     * @param User|null $user
     *
     * @return bool
     */
    protected function assignTask(Task $task, ?User $user = null): bool
    {

        $taskDeadlineTime = null;

        if (!is_null($task->deadline_date) && !is_null($task->deadline_time)) {

            list($hours, $minutes) = explode(":", $task->deadline_time);

            $taskDeadlineTime = Carbon::createFromFormat('d-m-Y', $task->deadline_date)
                ->setTime((int) $hours, (int) $minutes, 0, 0);

            if ($taskDeadlineTime->getTimestamp() < Carbon::now()->addMinute(10)->getTimestamp()) {
                $taskDeadlineTime = null;
            }
        }

        if (!is_null($user)) {
            $isCanAssign = $user
                ->workTables()
                ->where('is_working', 1)
                ->when(
                    !is_null($taskDeadlineTime),
                    function (Builder $query) use ($taskDeadlineTime) {
                        return $query
                            ->where('time_from', '<=', $taskDeadlineTime)
                            ->where('time_to', '>=', $taskDeadlineTime->addMinutes(10));
                    },
                    function (Builder $query) {
                        return $query
                            ->where('time_from', '<=', Carbon::now())
                            ->where('time_to', '>=', Carbon::now()->addMinutes(10));
                    }
                )
                ->get()
                ->isNotEmpty();
        } else {
            $user = UserWorkTable::getActiveUsers()
                ->sortBy(
                    function (User $user) {

                        $userMinutesToWork = $user->getActiveWorkTable()->time_to->diffInMinutes();

                        $userActualTasksCount = Task::getActualTasks($user)->count();

                        return $userMinutesToWork ? $userActualTasksCount / $userMinutesToWork : PHP_INT_MAX;
                    }
                )->filter(function (User $user) use ($task){
                    if(isset($task->order->channel)) {
                        if(in_array($task->order->channel->id, $user->channels()->pluck('channel_id')->toArray())) {
                            return true;
                        }
                    }

                    return false;
                })
                ->first();


            $isCanAssign = !is_null($user);
        }

        if ($isCanAssign) {
            $task->update(['performer_user_id' => $user->id]);
        }

        return $isCanAssign;
    }
}
