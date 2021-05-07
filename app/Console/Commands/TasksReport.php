<?php

namespace App\Console\Commands;

use App\Call;
use App\Services\Telephony\TelephonyFactory;
use Illuminate\Console\Command;
use App\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Jobs\TelegramMessage;
use App\TelegramReportSetting;
use App\ModelChange;
use App\Order;
use Ixudra\Curl\Facades\Curl;

/**
 * Class TasksReport формирует сообщение отчёта для отправки в телеграмм
 *
 * @package App\Console\Commands
 */
class TasksReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sent tasks report to telegram';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $time = Carbon::now();
        if (!TelegramReportSetting::where('time', $time->format('H:i'))->count()) {
            return;
        }
        $settings = TelegramReportSetting::where('time', $time->format('H:i'))->get();
        $yesterday = Carbon::yesterday();
        $today = Carbon::today();
        $message = ' ';
        foreach ($settings as $setting) {

            //отчёт по статусам заказов по пользователям
            if ($setting->orderStates->count()) {
                $orderStates = $setting->orderStates->map(function ($item) {
                    return $item->id;
                })->toArray();
                $orders = DB::table('order_states')
                    ->join('order_order_state', 'order_states.id', '=', 'order_order_state.order_state_id')
                    ->join('orders', 'order_order_state.order_id', '=', 'orders.id')
                    ->join('users', 'order_order_state.user_id', '=', 'users.id')
                    ->where('order_order_state.created_at', '>', $yesterday->toDateTimeString())
                    ->whereIn('order_states.id', $orderStates)
                    ->where('order_order_state.created_at', '<', $today->toDateTimeString())
                    ->groupBy('order_states.id')
                    ->select('users.id', 'users.name', 'order_states.name as state_name', 'order_states.id as states_id')
                    ->groupBy('users.id')
                    ->groupBy('order_order_state.order_id')
                    ->get();

                $readyOrders = [];
                foreach ($orders as $order) {
                    $flag = 0;
                    foreach ($readyOrders as $readyOrder) {
                        if ($readyOrder->id == $order->id && $readyOrder->states_id == $order->states_id) {
                            $readyOrder->count += 1;
                            $flag = 1;
                        }
                    }
                    if ($flag == 0) {
                        $order->count = 1;
                        array_push($readyOrders, $order);
                    }
                }

                $message .= __(
                    "*Report of order states :time*",
                    [
                        'time' => $yesterday->toDateString(),
                    ]
                );
                $orders = collect($readyOrders);
                $total = $orders->sum('count');
                $orders = $orders->groupBy('states_id');

                foreach ($orders as $value) {
                    $message .= "\n" . $value[0]->state_name;
                    foreach ($value as $item) {
                        $message .= "\n   " . $item->name . ": " . $item->count;
                    }
                    $message .= "\n*  " . __('Total') . '* ' . $value->sum('count') . "\n\n";
                }
                $message .= "\n*" . __('Total for all') . '* ' . $total . "\n\n";
            }

            //отчёт по стутусам задач по пользователям
            if ($setting->taskStates->count()) {
                $taskStates = $setting->taskStates->map(function ($item) {
                    return $item->id;
                })->toArray();
                $tasks = DB::table('task_states')
                    ->join('task_task_state', 'task_states.id', '=', 'task_task_state.task_state_id')
                    ->join('tasks', 'task_task_state.task_id', '=', 'tasks.id')
                    ->join('users', 'task_task_state.user_id', '=', 'users.id')
                    ->where('task_task_state.created_at', '>', $yesterday->toDateTimeString())
                    ->where('task_task_state.created_at', '<', $today->toDateTimeString())
                    ->whereIn('task_states.id', $taskStates)
                    ->groupBy('task_states.id')
                    ->select('users.id', 'users.name', 'task_states.name as state_name', 'task_states.id as states_id', DB::raw('count(*) as count'))
                    ->groupBy('users.id')
                    ->get();

                $tasks = collect($tasks);
                $total = $tasks->sum('count');
                $tasks = $tasks->groupBy('states_id');
                $message .= __(
                    "*Report of tasks :time*",
                    [
                        'time' => $yesterday->toDateString(),
                    ]
                );
                foreach ($tasks as $value) {
                    $message .= "\n" . $value[0]->state_name;
                    foreach ($value as $item) {
                        $message .= "\n   " . $item->name . ": " . $item->count;
                    }
                    $message .= "\n*  " . __('Total') . '* ' . $value->sum('count') . "\n\n";
                }
                $message .= "\n*" . __('Total for all') . '* ' . $total . "\n\n";
            }

            //отчёт по перенесённым задачам (выполненные задачи отсекаются)
            $changedDates = ModelChange::getModelsChenges(Task::class, 'deadline_date')
                ->where('created_at', '>', $yesterday->toDateTimeString())
                ->where('created_at', '<', $today->toDateTimeString())
                ->get();
            $message .= __(
                "*Transfered task`s dates for :time*",
                [
                    'time' => $yesterday->toDateString(),
                ]
            );
            $transfers = [];
            $transfersTasks = [];
            foreach ($changedDates as $changedDate) {
                if (!empty($changedDate->old_values()['deadline_date']) && $changedDate->old_values()['deadline_date'] != $changedDate->new_values()['deadline_date']) {
                    if (Task::find($changedDate->old_values()['id'])->currentState()->is_closed) {
                        continue;
                    }
                    $transfers[$changedDate->user->name][$changedDate->old_values()['id']] =  1;
                    $transfersTasks[$changedDate->old_values()['id']] = 1;
                }
            }
            $total = 0;
            foreach ($transfers as $key => $value) {
                $total += count($value);
                $message .= "\n   " . $key . ": " . count($value);
            }
            $message .= "\n*" . __('Total') . '* ' . count($transfersTasks) . "\n\n";
            //колличество созданных но не выполненных задач за день
            $tasksByDay = Task::where('deadline_date', '<=', $yesterday->toDateTimeString())
                ->get();
            $tasksNotCompleted = $tasksByDay->filter(function ($item) {
                if (empty($item->currentState())) return false;
                if (!empty($item->performer->id) && !$item->performer->isManager()) return false;
                return $item->currentState()->is_closed ? false : true;
            });
            $message .= "*" . __('Not completed actual tasks') . '* ' . $tasksNotCompleted->count() . "\n\n";
            //колличество подтверждённых но не собрынных заказов
            $confirmTime = Carbon::createFromFormat('H:i:s', $setting->confirm_time);
            $orderAll = Order::all();
            $yesterdayOrder = $yesterday->setTime($confirmTime->format('H'), $confirmTime->format('i'), 0)->toDateTimeString();
            $orderConfirmed = $orderAll->filter(function (Order $order) {
                if (!$order->currentState()->is_confirmed || $order->is_hidden) {
                    return false;
                }
                return true;
            });
            //Заказы подтверждены до 19:00
            $confirmedBefore19 = $orderConfirmed->filter(function (Order $order) use ($yesterdayOrder) {
                if ($order->currentState()->pivot->created_at < $yesterdayOrder) {
                    return true;
                }
                return false;
            });

            //Заказы подтверждены но не собраны
            $message .= "*" . __('Orders confirmed but not collected') . '* ' . $orderConfirmed->count() . ' из них ' . $confirmedBefore19->count() . ' подтверждены до ' . $confirmTime->format('H:i') .  "\n\n";

            //количество изменений статусов товарных позиций (OrderDetailStates)
            $yesterday = Carbon::yesterday();
            $today = Carbon::today();
            if ($setting->orderDetailStates->count()) {
                $orderDetailStates = $setting->orderDetailStates->map(function ($item) {
                    return $item->id;
                })->toArray();
                $orderDetails = DB::table('order_detail_states')
                    ->join('order_detail_order_detail_state', 'order_detail_states.id', '=', 'order_detail_order_detail_state.order_detail_state_id')
                    ->join('order_details', 'order_detail_order_detail_state.order_detail_id', '=', 'order_details.id')
                    ->join('users', 'order_detail_order_detail_state.user_id', '=', 'users.id')
                    ->where('order_detail_order_detail_state.created_at', '>', $yesterday->toDateTimeString())
                    ->where('order_detail_order_detail_state.created_at', '<', $today->toDateTimeString())
                    ->whereIn('order_detail_states.id', $orderDetailStates)
                    ->select('order_details.order_id as orderId', 'order_detail_order_detail_state.created_at as state_created_at', 'users.id', 'users.name', 'order_detail_states.name as state_name', 'order_detail_states.id as states_id')
                    ->get();
                $message .= __(
                    "*Report of order detail states :time*",
                    [
                        'time' => $yesterday->toDateString(),
                    ]
                );
                $orderDetails = collect($orderDetails);
                $total = $orderDetails->count();
                $orderDetails = $orderDetails->groupBy('states_id');

                foreach ($orderDetails as $state) {
                    $statusSum = 0;
                    $ordersSum = [];
                    $gruppedByName = $state->groupBy('name');
                    $message .= "\n" . $state[0]->state_name;
                    foreach ($gruppedByName as $name => $states) {
                        $message .= "\n   " . $name . ": " . $states->count();
                        $statusSum += $states->count();
                        foreach ($states as $nameState) {
                            $ordersSum[$nameState->orderId] = 1;
                        }
                    }
                    $message .= __(
                        "\n  In all for :statusSum in :orderSum orders\n\n",
                        [
                            'statusSum' => $statusSum,
                            'orderSum' => count($ordersSum)
                        ]
                    );
                }
                $message .= "\n*" . __('Total for all') . '* ' . $total . "\n\n";
            }

            //Получение количества минут в исходящих и входях вызовах
            $message .= __('Information API') . "\n";
            $token = config('telephony.beeline_token');
            $calls = Curl::to('https://cloudpbx.beeline.ru/apis/portal/records?dateFrom='
                . \Carbon\Carbon::yesterday()->format('Y-m-d') . 'T00%3A00%3A00.000Z&dateTo='
                . \Illuminate\Support\Carbon::today()->format('Y-m-d') . 'T00%3A00%3A00.000Z')
                ->withHeader('X-MPBX-API-AUTH-TOKEN: ' . $token)
                ->asJson(true)
                ->get();

            //Входящий вызов
            $inboundCount = 0;
            $inbound = 0;
            //Исходящий вызов
            $outboundCount = 0;
            $outbound = 0;
            foreach ($calls as $call) {
                if ($call['direction'] == 'OUTBOUND') {
                    $outbound += $call['duration'];
                    $outboundCount++;
                } else {
                    $inbound += $call['duration'];
                    $inboundCount++;
                }
            }

            $inbound /= 60000;
            $outbound /= 60000;
            $inboundMean = $inbound / $inboundCount;
            $outboundMean = $outbound / $outboundCount;

            $message .= __('Total min inbound') . ' ' . (int) $inbound . "\n";
            $message .= __('Inbound mean') . ' ' . $inboundMean . "\n";
            $message .= __('Count record inbound') . $inboundCount . "\n\n";
            $message .= __('Total min outbound') . ' ' . (int) $outbound . "\n";
            $message .= __('Outbound mean') . ' ' . $outboundMean . "\n";
            $message .= __('Count record outbound') . $outboundCount . "\n\n";

            //Получаем список вызовов
            $outgoingCount = 0;
            $ingoingCount = 0;
            $outgoingLength = 0;
            $ingoingLength = 0;
            $outMean = 0;
            $inMean = 0;
            $ingoingUnaccepted = 0;

            $calls = Call::where('created_at', '>=', Carbon::yesterday()->setTime(00, 00, 00)->format('Y-m-d H:i:s'))
                ->where('created_at', '<=', Carbon::today()->setTime(00, 00, 00)->format('Y-m-d H:i:s'))
                ->get();

            //заменяем Call на соответствующие телефониям use App\Services\Telephony\Interfaces\CallInterface
            $calls = $calls->map(function (Call $call) {
                $telephony = (new TelephonyFactory)->getTelephonyFactory($call->telephony_name);
                return $telephony->call()::find($call->id);
            });
            /**
             * @var $call App\Services\Telephony\Interfaces\CallInterface
             */
            foreach ($calls as $call) {
                if ($call->isOutgoing()) {
                    $outgoingCount++;
                    $outgoingLength += $call->length;
                } else {
                    $ingoingCount++;
                    $ingoingLength += $call->length;
                    if ($call->length == null) {
                        $ingoingUnaccepted++;
                    }
                }
            }

            $message .= __('Information DB') . "\n";
            $outgoingLength /= 60;
            $ingoingLength /= 60;
            if ($outgoingLength != 0) {
                $outMean = $outgoingLength / $outgoingCount;
            }

            if ($ingoingLength != 0) {
                $inMean = $ingoingLength / $ingoingCount;
            }

            $message .= __(
                'Count outbound :outgoingCount , length :outgoingLength',
                [
                    'outgoingCount' => $outgoingCount,
                    'outgoingLength' => (int) $outgoingLength
                ]
            ) . "\n";
            $message .= __('Calls mean') . $outMean . "\n";
            $message .= __(
                'Count inbound :ingoingCount , length :ingoingLength',
                [
                    'ingoingCount' => $ingoingCount,
                    'ingoingLength' => (int) $ingoingLength
                ]
            ) . "\n";
            $message .= __('Calls mean') . $inMean . "\n";
            $message .= __('Count unaccepted') . $ingoingUnaccepted;

            $message = [
                'chat_id' => $setting->chat_id,
                'text' => "$message",
                'parse_mode' => 'Markdown',
            ];

            TelegramMessage::dispatch($message)->onQueue('telegram_message');
        }
    }
}
