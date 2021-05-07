<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\OrderAlert;
class OrderAlertController extends Controller
{
    /**
     * OrderAlertController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:orderAlert-list');
        $this->middleware('permission:orderAlert-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:orderAlert-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:orderAlert-delete', ['only' => ['delete']]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $orderAlerts = OrderAlert::orderBy('id', 'ASC')->limit(50)->get();
        return view('order-alerts-trashold.index', compact('orderAlerts'));
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){
        return view('order-alerts-trashold.create');
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        if(isset($request->trashold)){
            $this->validate($request, [
                'trashold' => 'required|integer|min:1',
            ]);
            $orderAlert = new OrderAlert;
            $orderAlert->trashold = $request->trashold;
            $orderAlert->save();
            return redirect()->route('order-alerts.index');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\OrderState $orderState
     * @return \Illuminate\Http\Response
     */
    public function show(OrderAlert $orderAlert)
    {
        return view('order-alerts-trashold.show', compact('orderAlert'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\OrderAlert $orderAlert
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderAlert $orderAlert ){
        return view('order-alerts-trashold.edit', compact('orderAlert'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\OrderAlert $orderAlert
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderAlert $orderAlert){
        if(isset($request->trashold)){
            $this->validate($request, [
                'trashold' => 'required|integer|min:1',
            ]);
            $orderAlert->update(['trashold' => $request->trashold]);
            return redirect()->route('order-alerts.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param OrderAlert $orderAlert
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(OrderAlert $orderAlert){
        $orderAlert->delete();
        return redirect()->route('order-alerts.index');
    }
 
}
