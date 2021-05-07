<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\Services\Report\Commands\CommandInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderStatesHandler extends AbstractHandler
{

    /**
     * получить изменения определенных статусов вместе с пользователями которые их сделали за промежуток времени
     *
     * @param Carbon $timeFrom
     * @param Carbon $timeTo
     * @param Collection $orderStates
     * @param Collection|null $users
     * @return Collection
     */
    public function getStatesChangesWithUsers(Carbon $timeFrom, Carbon $timeTo, Collection $orderStates, Collection $users = null): Collection
    {

        $query = DB::table('order_states')
            ->join('order_order_state', 'order_states.id', '=', 'order_order_state.order_state_id')
            ->join('orders', 'order_order_state.order_id', '=', 'orders.id')
            ->join('users', 'order_order_state.user_id', '=', 'users.id')
            ->where('order_order_state.created_at', '>', $timeFrom->toDateTimeString())
            ->whereIn('order_states.id', $orderStates->pluck('id'))
            ->where('order_order_state.created_at', '<', $timeTo->toDateTimeString())
            ->groupBy('order_states.id')
            ->select('users.id as user_id', 'users.name as user_name', 'order_states.name as state_name', 'order_states.id as state_id')
            ->groupBy('users.id')
            ->groupBy('order_order_state.order_id');

        if ($users->count() != 0) {
            $query->whereIn('users.id', $users->pluck('id'));
        }
        return $query->get();

    }

    /**
     * сгруппировать по статусам и посчитать изменения для каждого
     *
     * @param Collection $statesChanges
     * @return Collection
     */
    public function countStatesForUser(Collection $statesChanges): Collection
    {
        $processedStates = [];
        foreach ($statesChanges as $change) {
            $flag = 0;
            foreach ($processedStates as $readyOrder) {
                if ($readyOrder->state_id == $change->state_id && $readyOrder->user_id == $change->user_id) {
                    $readyOrder->count += 1;
                    $flag = 1;
                }
            }
            if ($flag == 0) {
                $change->count = 1;
                array_push($processedStates, $change);
            }
        }
        return collect($processedStates);
    }

    /**
     * add information text in command
     *
     * @param CommandInterface $command
     * @return CommandInterface
     */
    public function addInfo(CommandInterface $command):CommandInterface
    {

        $command->getReportMessage()->LF()->LF();
        $command->getReportMessage()->addText(__(
            "*Report of order states :timeFrom to :timeTo*",
            [
                'timeFrom' => $command->getReportRequest()->getTimeFrom()->toDateTimeString(),
                'timeTo' => $command->getReportRequest()->getTimeTo()->toDateTimeString(),
            ]
        ));
        $stateChanges = $this->getStatesChangesWithUsers($command->getReportRequest()->getTimeFrom(), $command->getReportRequest()->getTimeTo(), $command->getReportRequest()->getOrderStates(), $command->getReportRequest()->getUsers());
        $groupedStates = $this->countStatesForUser($stateChanges)->groupBy('state_id');
        $allChanges = $stateChanges->count();

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