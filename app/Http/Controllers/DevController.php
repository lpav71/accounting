<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\Cashbox;
use App\Jobs\TelegramMessage;
use App\ModelChange;
use App\Operation;
use App\OperationState;
use App\Order;
use App\Product;
use App\Traits\ModelLogger;
use Illuminate\Http\Request;
use App\OrderDetail;
use Illuminate\Support\Facades\DB;
use Session;
use Telegram\Bot\Laravel\Facades\Telegram;

class DevController extends Controller
{
    public function lostProducts()
    {
        $orders = \App\Order::all()->filter(function (\App\Order $order) {
            foreach ($order->orderDetails as $orderDetail) {
                if ($orderDetail->store_id == 1) {
                    if ($orderDetail->store->getReservedQuantity($orderDetail->id) > 0) {
                        return true;
                    }
                }
            }
        });

        foreach ($orders as $order) {
            echo('<div>');
            echo('<br>');
            echo(' номер заказа ');
            echo($order->order_number);
            foreach ($order->orderDetails as $orderDetail) {
                if ($orderDetail->store_id == 1 && $orderDetail->store->getReservedQuantity($orderDetail->id) > 0) {
                    echo('<div>');
                    echo($orderDetail->product->name);
                    echo(' колличество ');
                    echo($orderDetail->store->getReservedQuantity($orderDetail->id));
                    echo('</div>');
                }
            }
            echo('</div>');
        }
    }

    /**
     * Экшн для минорных задач дебага
     */
    public function debugging(Request $request)
    {
//        OperationState::create([
//            'name' => 'Не подтверждено',
//            'non_confirmed' => 1,
//
//        ]);
//        OperationState::create([
//            'name' => 'Подтверждено',
//            'color' => '#008000',
//            'is_confirmed' => 1,
//        ]);
//        $operations = Operation::where('storage_type', '=', Cashbox::class)->get();
//        /**
//         * @var $operation Operation
//         */
//        foreach ($operations as $operation) {
//            $operation->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);
//        }
    }
}
