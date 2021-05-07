<?php

namespace App\Console\Commands;

use App\CdekState;
use App\Order;
use App\Task;
use App\TaskState;
use Appwilio\CdekSDK\CdekClient;
use Appwilio\CdekSDK\Common\Order as CdekOrder;
use Appwilio\CdekSDK\Requests\StatusReportRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class OrdersCdekUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdek:orders-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновление информации о заказах из API СДЭК';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(3600);

        $orders = Order::where('delivery_shipping_number', '!=', '')
            ->where('carrier_id', '!=', '')
            ->where('is_hidden',0)
            ->get();
        $orders = $orders->filter(
            function (Order $item) {
                return $item->currentState()->cdek_not_load == 0;
            }
        );
        $orders = $orders->filter(
            function (Order $item) {
                return (bool)(preg_match('/^\d+$/', $item->delivery_shipping_number));
            }
        );
        $orders = $orders->filter(
            function (Order $item) {
                if (null === $item->currentCdekState()) {
                    return true;
                }
                if ($item->currentCdekState()->is_last_state) {
                    return false;
                }

                return true;
            }
        );

        $checkStack = [];

        /**
         * @var Order $order
         */
        foreach ($orders as $order) {
            $check = null;
            $carrierConfig = $order->carrier->getConfigVars();
            if ($carrierConfig->get('operator') == 'cdek') {
                $check['account'] = $carrierConfig['operator_account'];
                $check['secure'] = $carrierConfig['operator_secure'];
                $check['delivery_shipping_number'] = $order->delivery_shipping_number;
            } else {
                continue;
            }

            $checkStack[] = $check;
        }
        $checkStack = collect($checkStack);
        $checkStack = $checkStack->groupBy('account');
        $responses = [];
        foreach ($checkStack as $item) {
            $client = new CdekClient($item[0]['account'], $item[0]['secure']);
            $request = (new StatusReportRequest())->setShowHistory();
            foreach ($item as $value) {
                $request->addOrder(new CdekOrder(['DispatchNumber' => $value['delivery_shipping_number']]));
            }
            $responses[] = $client->sendStatusReportRequest($request);
        }
        $orders = [];
        foreach ($responses as $response) {
            $orders = array_merge($orders, $response->getOrders());
        }
        $orders = collect($orders);
        $orders = $orders->where('ActNumber', '!==', null);

        /**
         * @var CdekOrder $order
         */
        foreach ($orders as $order) {
            $orderCRM = Order::where('delivery_shipping_number', $order->getDispatchNumber())->first();
            $syncStates = [];
            foreach ($order->getStatus()->getStates() as $state) {
                if (CdekState::where('state_code', $state->Code)->count() == 0) {
                    CdekState::create(
                        [
                            'state_code' => $state->Code,
                            'name' => $state->Description,
                        ]
                    );
                }
                /**
                 * @var CdekState $stateCRM
                 */
                $stateCRM = CdekState::where('state_code', $state->Code)->first();
                if ($stateCRM->orders->where('id', $orderCRM->id)->isEmpty() && $stateCRM->need_task && !$stateCRM->is_daily) {
                    $newTask = Task::create(
                        [
                            'name' => $stateCRM->task_name,
                            'description' => $stateCRM->task_description,
                            'customer_id' => $orderCRM->customer_id,
                            'order_id' => $orderCRM->id,
                            'task_priority_id' => $stateCRM->task_priority_id,
                            'task_type_id' => $stateCRM->task_type_id,
                            'deadline_date' => Carbon::now()->addDays($stateCRM->task_date_diff)->format('d-m-Y'),
                        ]
                    );
                    $stateNew = TaskState::where('is_new', 1)->first();
                    if ($stateNew instanceof TaskState) {
                        $newTask->states()->save($stateNew);
                    }
                }
                $date = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $state->Date->format('Y-m-d H:i:s'),
                    $state->Date->format('P')
                );
                $date->setTimezone(date_default_timezone_get());
                $syncStates[$stateCRM->id] = [
                    'created_at' => $date->toDateTimeString()
                ];
            }
            /**
             * @var Order $orderCRM
             */
            $orderCRM->cdekStates()->sync($syncStates);
            if (isset($stateCRM) && $orderCRM->currentCdekState()->need_task && $orderCRM->currentCdekState(
                )->is_daily && $orderCRM->currentCdekState()->pivot->created_at->diffInHours(
                ) > 24 && !$orderCRM->currentCdekState()->pivot->task_made) {
                $newTask = Task::create(
                    [
                        'name' => $stateCRM->task_name,
                        'description' => $stateCRM->task_description,
                        'customer_id' => $orderCRM->customer_id,
                        'order_id' => $orderCRM->id,
                        'task_priority_id' => $stateCRM->task_priority_id,
                        'task_type_id' => $stateCRM->task_type_id,
                        'deadline_date' => Carbon::now()->addDays($stateCRM->task_date_diff)->format('d-m-Y'),
                    ]
                );
                $stateNew = TaskState::where('is_new', 1)->first();
                if ($stateNew) {
                    $newTask->states()->save($stateNew);
                }
                $pivotItem = $orderCRM->currentCdekState()->pivot;
                $pivotItem->task_made = true;
                $pivotItem->save();
            }
        }
    }
}
