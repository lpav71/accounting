<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\ModelChange;
use App\Services\Report\Commands\CommandInterface;
use App\Task;

class ReassignTasksHandler extends AbstractHandler
{

    /**
     * @param CommandInterface $command
     * @return CommandInterface
     */
    public function addInfo(CommandInterface $command): CommandInterface
    {

        $changedDatesQuery = ModelChange::getModelsChenges(Task::class, 'deadline_date')
            ->where('created_at', '>', $command->getReportRequest()->getTimeFrom()->toDateTimeString())
            ->where('created_at', '<', $command->getReportRequest()->getTimeTo()->toDateTimeString());
        if ($command->getReportRequest()->getUsers()->count() != 0) {
            $changedDatesQuery = $changedDatesQuery->whereIn('user_id', $command->getReportRequest()->getUsers()->pluck('id'));
        }
        $changedDates = $changedDatesQuery->get();
        $command->getReportMessage()->LF()->LF();
        $command->getReportMessage()->addText(__(
            "*Transfered task`s dates :timeFrom to :timeTo*",
            [
                'timeFrom' => $command->getReportRequest()->getTimeFrom()->toDateTimeString(),
                'timeTo' => $command->getReportRequest()->getTimeTo()->toDateTimeString(),
            ]
        ));

        $transfers = [];
        $transfersTasks = [];
        foreach ($changedDates as $changedDate) {
            if (!empty($changedDate->old_values()['deadline_date']) && $changedDate->old_values()['deadline_date'] != $changedDate->new_values()['deadline_date']) {
                if (Task::find($changedDate->old_values()['id'])->currentState()->is_closed) {
                    continue;
                }
                $transfers[$changedDate->user->name][$changedDate->old_values()['id']] = 1;
                $transfersTasks[$changedDate->old_values()['id']] = 1;
            }
        }
        $total = 0;
        foreach ($transfers as $key => $value) {
            $total += count($value);
            $command->getReportMessage()->LF()->SPC()->addText($key)->SPC()->addText(count($value));
        }
        $command->getReportMessage()->LF()->SPC()->addText('*' . __('Total') . '*')->SPC()->addText(count($transfersTasks));
        return $command;
    }
}