<?php
namespace App\Http\Resources;

use DB;
use App\OrderState;
use App\Order;

class OrderResources {
    
    //count confirmed orders
    //time of last order state's creating
    public static function LatestStates(){
        
        return DB::table('order_order_state')
            ->select('order_id', DB::raw('MAX(created_at) as last_state_created_at'))
            ->groupBy('order_id');
        
    }
    
    //orders with need states
    public static function OrderWithLastStates(){
        return DB::table('order_order_state')
            ->joinSub(self::LatestStates(), 'latest_states', function ($join) {
                $join->on('order_order_state.order_id', '=', 'latest_states.order_id')
                    ->on('order_order_state.created_at', '=', 'latest_states.last_state_created_at');
            })->join('orders', 'orders.id', 'order_order_state.order_id')
            ->where('orders.is_hidden', 0);
    }
    
    public static function CountRedOrders(){
        $newOrderStateId = self::NewOrderStateId();
        if($newOrderStateId){
            return self::RedClients($newOrderStateId)->count();
        }else{
            return __("Mark 'new' order state");
        }
    }
    
    public static function FilterRedOrders(){
        $newOrderStateId = self::NewOrderStateId();
        if($newOrderStateId){
            return self::RedClients($newOrderStateId)->pluck('id')->toArray();
        }else{
            return false;
        }
    }
    
    public static function NewOrderStateId(){
        return OrderState::where('is_new', 1)->first() !== null ? OrderState::where('is_new', true)->first()->id : false;
    }
    
    public static function RedClients($newOrderStateId){
        $redClients = self::OrderWithLastStates()
            ->where('order_order_state.order_state_id', $newOrderStateId)
            ->pluck('order_order_state.order_id as id');
        $redClients = Order::find($redClients)->filter(function (Order $order) {
            if ($order->tasks->count()) {
                return false;
            } else {
                return true;
            }
        });
        return $redClients;
    }
}
