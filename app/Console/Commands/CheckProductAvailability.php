<?php

namespace App\Console\Commands;

use App\Operation;
use App\OrderDetail;
use App\Product;
use App\Store;
use App\Task;
use App\TaskPriority;
use App\TaskState;
use App\TaskType;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckProductAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of orders, "the expectation of having" if there were products, create task';

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
        //Получаем операции за последний час
        $operations = Operation::whereBetween('created_at', [Carbon::now()->minute(0)->second(0)->subHour(1), Carbon::now()->minute(0)->second(0)])
            ->where(['operable_type' => 'App\Product'])
            ->where(['storage_type' => 'App\Store'])
            ->orderBy('id')
            ->get();

        //Получаем статус, тип и приоритет для задачи
        $taskState = TaskState::where(['is_new' => 1])->first();
        $taskType = TaskType::where(['is_store' => 1])->first();
        $taskPriority = TaskPriority::where(['is_very_urgent' => 1])->first();

        //Проверяем измения исходя из операций
        $changeQuantity = [];
        /**
         * @var $operation Operation
         */
        foreach ($operations as $operation) {
            switch ($operation->type) {
                case "D":
                    if (array_key_exists($operation->operable_id, $changeQuantity)) {
                        $changeQuantity[$operation->operable_id] += $operation->quantity;
                    } else {
                        $changeQuantity[$operation->operable_id] = $operation->quantity;
                    }
                    break;
                case "C":
                    if (array_key_exists($operation->operable_id, $changeQuantity)) {
                        $changeQuantity[$operation->operable_id] -= $operation->quantity;
                    } else {
                        $changeQuantity[$operation->operable_id] = $operation->quantity * -1;
                    }
                    break;
            }
        }
        foreach ($changeQuantity as $key => $value) {

            //Если изменения отрацательные, значит 0 в складе быть не могло, либо если 0 значит товар занесли и сразу сняли
            if ($value > 0) {
                $needOperation = $operations->filter(function ($item) use ($key) {
                    return $item->operable_id == $key;
                })->sortBy('id')->first();
                $store = Store::find($needOperation->storage_id);

                //Если перед 1й операцией за час с этим operableId кол-во было 0
                if ($store->getQuantityBeforeOperation($key, $needOperation) == 0) {
                    $product = Product::find($needOperation->operable_id);
                    $parents = $product->parent;
                    if (!count($parents)) {
                        $orders = $product->orderDetails->map(function (OrderDetail $orderDetail) {
                            // это уже статус и если статус нам подходит то создавать задачу
                            if ($orderDetail->order->currentState()->is_waiting_for_product) {
                                if (!$orderDetail->order->is_hidden) {
                                    return $orderDetail->order;
                                }
                            }
                        })->reject(function ($item) {
                            return $item == null;
                        });
                    } else {
                        $orders = collect();
                        foreach ($parents as $parent) {
                            $tmp = $parent->orderDetails->map(function (OrderDetail $orderDetail) {

                                // это уже статус и если статус нам подходит то создавать задачу
                                if ($orderDetail->order->currentState()->is_waiting_for_product) {
                                    if (!$orderDetail->order->is_hidden) {
                                        return $orderDetail->order;
                                    }
                                }
                            })->reject(function ($item) {
                                return $item == null;
                            });
                            if (count($tmp)) {
                                $orders->push($tmp);
                            }
                        }
                        $orders = $orders->collapse();
                    }

                    //Если есть заказы в которых нужен данный продукт
                    if (count($orders)) {
                        foreach ($orders as $order) {
                            $task = Task::create(
                                [
                                    'name' => __('Product is in stock'),
                                    'description' => __('Product appeared in stock') . $store->name,
                                    'order_id' => $order->id,
                                    'customer_id' => $order->customer->id,
                                    'task_type_id' => $taskType->id,
                                    'task_priority_id' => $taskPriority->id,
                                    'deadline_date' => Carbon::today()->format('d-m-Y')
                                ]);
                            $task->states()->save($taskState);
                        }
                    }
                }
            }
        }
    }
}
