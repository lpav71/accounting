<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\Order;
use App\OrderState;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
//        if (!$request->carriers_id && !$request->order_states_id && (!preg_match('/^'.str_replace('/','\/', route('home')).'\/\?/', url()->previous()) || (!$request->date_estimated_delivery && url()->current() === route('home')))) {
//            //MYTODO Временный костыль до разработки настройки форм
//            return redirect(route('home',['carriers_id' => [1], 'order_states_id' => [1,2,3,4,5,7,8,9,10,11,12]]));
//        }
//        $date = $request->date_estimated_delivery ?: Carbon::now()->format('d-m-Y');
//        $orders = Order::all()->where('is_hidden', 0)->where('date_estimated_delivery', $date)->sortBy('delivery_start_time');
//        if (is_array($request->carriers_id) && count($request->carriers_id)) {
//            $orders = $orders->whereIn('carrier_id', $request->carriers_id);
//        }
//        if (is_array($request->order_states_id) && count($request->order_states_id)) {
//            $orders = $orders->filter(function(Order $order) use ($request) {
//                return in_array($order->currentState()->id, $request->order_states_id);
//            });
//        }
//        $orderStates = OrderState::all()->pluck('name', 'id');
//        $availableCarriers = Carrier::all()->pluck('name', 'id');

//        return view('home', compact('date', 'orders', 'orderStates', 'availableCarriers'));

        if (\Auth::user()->can('order-list')) {
            return redirect(route('orders.index'));
        }

        return view('home');
    }
}
