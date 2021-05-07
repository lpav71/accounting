<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\Services\Report\Commands\CommandInterface;
use Illuminate\Support\Facades\DB;

/**
 * количество изменений статусов товарных позиций (OrderDetailStates)
 *
 * Class OrderDetailStatesHandler
 * @package App\Services\Report\ChainOfResponsibility
 */
class OrderDetailStatesHandler extends AbstractHandler
{

    /**
     * @inheritDoc
     */
    public function addInfo(CommandInterface $command): CommandInterface
    {
        if ($command->getReportRequest()->getOrderDetailStates()->count() == 0) {
            return $command;
        }
        $query = DB::table('order_detail_states')
            ->join('order_detail_order_detail_state', 'order_detail_states.id', '=', 'order_detail_order_detail_state.order_detail_state_id')
            ->join('order_details', 'order_detail_order_detail_state.order_detail_id', '=', 'order_details.id')
            ->join('users', 'order_detail_order_detail_state.user_id', '=', 'users.id')
            ->where('order_detail_order_detail_state.created_at', '>', $command->getReportRequest()->getTimeFrom()->toDateTimeString())
            ->where('order_detail_order_detail_state.created_at', '<', $command->getReportRequest()->getTimeTo()->toDateTimeString())
            ->whereIn('order_detail_states.id', $command->getReportRequest()->getOrderDetailStates()->pluck('id'))
            ->select('order_details.order_id as orderId', 'order_detail_order_detail_state.created_at as state_created_at', 'users.id', 'users.name', 'order_detail_states.name as state_name', 'order_detail_states.id as states_id');

        if ($command->getReportRequest()->getUsers()->count() != 0) {
            $query->whereIn('users.id', $command->getReportRequest()->getUsers()->pluck('id'));
        }

        $orderDetails = $query->get();
        $command->getReportMessage()->LF()->LF()->addText(__(
            "*Report of order detail states :timeFrom to :timeTo*",
            [
                'timeFrom' => $command->getReportRequest()->getTimeFrom()->toDateTimeString(),
                'timeTo' => $command->getReportRequest()->getTimeTo()->toDateTimeString()
            ]
        ));
        $orderDetails = collect($orderDetails);
        $total = $orderDetails->count();
        $orderDetails = $orderDetails->groupBy('states_id');

        foreach ($orderDetails as $state) {
            $statusSum = 0;
            $ordersSum = [];
            $groupedByName = $state->groupBy('name');
            $command->getReportMessage()->LF()->addText($state[0]->state_name);
            foreach ($groupedByName as $name => $states) {
                $command->getReportMessage()->LF()->SPC()->SPC()->addText($name)->SPC()->addText($states->count());
                $statusSum += $states->count();
                foreach ($states as $nameState) {
                    $ordersSum[$nameState->orderId] = 1;
                }
            }
            $command->getReportMessage()->LF()->SPC()->SPC()->addText(__('In all for :statusSum in :orderSum orders', [
                'statusSum' => $statusSum,
                'orderSum' => count($ordersSum)
            ]));
        }
        $command->getReportMessage()->LF()->addText("*" . __('Total for all') . "*")->SPC()->addText($total);

        return $command;
    }
}