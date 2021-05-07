<?php

namespace App\Console\Commands;

use App\Order;
use App\Task;
use App\TaskPriority;
use App\TaskState;
use App\TaskType;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateCloseOrderTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'close-order:tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $orders = Order::whereNotNull('date_estimated_delivery')->where('date_estimated_delivery', '>=', '2020-05-01')
            ->where('date_estimated_delivery', '=', $date = Carbon::today()->subDay(2)->format('Y-m-d'))
            ->where('is_hidden', '=', 0)
            ->get();
        $orders = $orders->filter(function (Order $order) {
            return $order->carrier->close_order_task;
        });
        /**
         * @var $order Order
         */
        foreach ($orders as $order) {
            if (!$order->currentState()->is_successful && !$order->currentState()->is_failure) {
                $task = Task::create(
                    [
                        'name' => __("[Auto] Find out what's with the order"),
                        'description' => __("[Auto] Find out what's with the order"),
                        'order_id' => $order->id,
                        'customer_id' => $order->customer->id,
                        'task_type_id' => TaskType::where(['is_basic' => 1])->first()->id,
                        'task_priority_id' => TaskPriority::where(['is_normal' => 1])->first()->id,
                        'deadline_date' => Carbon::today()->format('d-m-Y'),
                        'check_related_order' => 1
                    ]
                );
                $task->states()->save(TaskState::where(['is_new' => 1])->first());
            }
        }
    }
}
