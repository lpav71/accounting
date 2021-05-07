<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\Services\Report\Commands\CommandInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class TaskStatesHandler
 * @package App\Services\Report\ChainOfResponsibility
 */
class TaskStatesHandler extends AbstractHandler
{

    /**
     * @param Carbon $timeFrom
     * @param Carbon $timeTo
     * @param Collection $taskStates
     * @param Collection|null $users
     * @return Collection
     */
    public function getStatesChangesWithUsers(Carbon $timeFrom, Carbon $timeTo, Collection $taskStates, Collection $users = null): Collection
    {

        $query = DB::table('task_states')
            ->join('task_task_state', 'task_states.id', '=', 'task_task_state.task_state_id')
            ->join('tasks', 'task_task_state.task_id', '=', 'tasks.id')
            ->join('users', 'task_task_state.user_id', '=', 'users.id')
            ->where('task_task_state.created_at', '>', $timeFrom->toDateTimeString())
            ->where('task_task_state.created_at', '<', $timeTo->toDateTimeString())
            ->whereIn('task_states.id', $taskStates->pluck('id'))
            ->groupBy('task_states.id')
            ->select('users.id as user_id', 'users.name as user_name', 'task_states.name as state_name', 'task_states.id as state_id', DB::raw('count(*) as count'))
            ->groupBy('users.id');

        if ($users->count() != 0) {
            $query->whereIn('users.id', $users->pluck('id'));
        }
        return $query->get();

    }

    /**
     * @param CommandInterface $command
     * @return CommandInterface
     */
    public function addInfo(CommandInterface $command): CommandInterface
    {
        $command->getReportMessage()->LF()->LF();
        $command->getReportMessage()->addText(__(
            "*Report of task states :timeFrom to :timeTo*",
            [
                'timeFrom' => $command->getReportRequest()->getTimeFrom()->toDateTimeString(),
                'timeTo' => $command->getReportRequest()->getTimeTo()->toDateTimeString(),
            ]
        ));
        $stateChanges = $this->getStatesChangesWithUsers($command->getReportRequest()->getTimeFrom(), $command->getReportRequest()->getTimeTo(), $command->getReportRequest()->getTaskStates(), $command->getReportRequest()->getUsers());
        $groupedStates = collect($stateChanges);
        $allChanges = $groupedStates->sum('count');
        $groupedStates = $groupedStates->groupBy('state_id');

        foreach ($groupedStates as $groupedState) {
            $command->getReportMessage()->LF()->SPC()->addText($groupedState[0]->state_name);
            foreach ($groupedState as $user) {
                $command->getReportMessage()->LF()->SPC()->SPC()->addText($user->user_name)->SPC()->addText($user->count);
            }
            $command->getReportMessage()->LF()->SPC()->addText('*' . __('Total') . '*')->SPC()->addText($groupedState->sum('count'));
        }
        $command->getReportMessage()->LF()->LF()->addText('*' . __('Total for all') . '*')->SPC()->addText($allChanges);

        return $command;
    }

}