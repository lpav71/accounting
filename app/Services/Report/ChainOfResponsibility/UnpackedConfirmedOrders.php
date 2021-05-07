<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\Order;
use App\OrderState;
use App\Services\Report\Commands\CommandInterface;
use DB;

class UnpackedConfirmedOrders extends AbstractHandler
{


    /**
     * @inheritDoc
     */
    public function addInfo(CommandInterface $command): CommandInterface
    {
        $confirmDateTime = clone $command->getReportRequest()->getTimeTo();
        $confirmDateTime = $confirmDateTime->setTime($command->getReportRequest()->getConfirmTime()->format('H'), $command->getReportRequest()->getConfirmTime()->format('i'));

        $latestStates = DB::table('order_order_state')
            ->select('order_id', DB::raw('MAX(created_at) as last_state_created_at'))
            ->groupBy('order_id');

        //orders with need states
        $orderWithLastStates = DB::table('order_order_state')
            ->joinSub($latestStates, 'latest_states', function ($join) {
                $join->on('order_order_state.order_id', '=', 'latest_states.order_id')
                    ->on('order_order_state.created_at', '=', 'latest_states.last_state_created_at');
            })->join('orders', 'orders.id', 'order_order_state.order_id')
            ->where('orders.is_hidden', 0);
        $confirmedStateId = OrderState::where('is_confirmed', true)->first()->id;
        $confirmedOrders = $orderWithLastStates
            ->where('order_order_state.order_state_id', $confirmedStateId)
            ->select(['orders.id'])
            ->get()
            ->pluck('id')
            ->toArray();
        $confirmedOrders = Order::whereIn('id', $confirmedOrders)->get();

        //Заказы подтверждены до confirm_time
        $confirmedBefore19 = $confirmedOrders->filter(function (Order $order) use ($confirmDateTime) {
            if ($order->currentState()->pivot->created_at->lt($confirmDateTime)) {
                return true;
            }
            return false;
        });
        $command->getReportMessage()->LF()->LF()->addText('*' . __('Orders confirmed but not packed') . '*')->SPC()->addText($confirmedOrders->count());
        $command->getReportMessage()->LF()->addText('*' . __('Orders confirmed before :time but not packed', ['time' => $confirmDateTime->format('H:i')]) . '*')->SPC()->addText($confirmedBefore19->count());

        return $command;
    }
}