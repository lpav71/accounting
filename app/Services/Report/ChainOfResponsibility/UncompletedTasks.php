<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\Services\Report\Commands\CommandInterface;
use App\Task;
use Illuminate\Support\Carbon;

/**
 * колличество созданных но не выполненных задач за день
 *
 * Class UncompletedTasks
 * @package App\Services\Report\ChainOfResponsibility
 */
class UncompletedTasks extends AbstractHandler
{

    /**
     * @param CommandInterface $command
     * @return CommandInterface
     */
    public function addInfo(CommandInterface $command): CommandInterface
    {
        $tasks = Task::select('tasks.*')
            ->where(
                'tasks.deadline_date',
                '<=',
                $command->getReportRequest()->getTimeTo()->toDateTimeString()
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
            ->get();
        $count = $tasks->filter(function (Task $task){
            if (empty($task->performer->id) || $task->performer->isManager()){
                return true;
            }
        })->count();

        $command->getReportMessage()->LF()->LF();
        $command->getReportMessage()->addText('*' . __('Not completed actual tasks') . '*')->SPC()->addText($count);
        return $command;
    }


}