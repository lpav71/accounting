<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\CarrierGroup;
use App\ExpenseSettings;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarrierGroupController extends Controller
{
    /**
     * CarrierGroupController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:carrier-list');
        $this->middleware('permission:carrier-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:carrier-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:carrier-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('carrier-groups.index', ['carrier_groups' => CarrierGroup::orderBy('id', 'ASC')->paginate(25)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('carrier-groups.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:carrier_groups,name',
            'user_id' => 'array',
            'carrier_id' => 'array'
        ]);

        DB::transaction(function () use ($request){
            $carrierGroup = CarrierGroup::create($request->input());
            $carrierGroup->users()->sync($request->get('user_id'));
            $carrierGroup->carriers()->saveMany(Carrier::whereIn('id', $request->get('carrier_id'))->get());
        });

        return redirect()->route('carrier-group.index')->with('success', __('Carrier group created successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return view('carrier-groups.edit', ['carrier_group' => CarrierGroup::find($id)]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'user_id' => 'array',
            'carrier_id' => 'array'
        ]);

        DB::transaction(function () use ($request, $id){
            $carrierGroup = CarrierGroup::find($id);
            $carrierGroup->update($request->input());
            $carrierGroup->users()->sync($request->get('user_id'));

            if(!empty($request->get('carrier_id'))) {
                foreach ($carrierGroup->carriers as $carrier) {
                    $carrier->update([
                        'carrier_group_id' => null
                    ]);
                    
                }
                $carrierGroup->carriers()->saveMany(Carrier::whereIn('id', $request->get('carrier_id'))->get());

                //Обновление способов доставки в расходах
                /**
                 * @var $expenseSetting ExpenseSettings
                 */
                foreach ($carrierGroup->expenseSettings as $expenseSetting) {
                    //Обнуляем все способы
                    $expenseSetting->carriers()->sync([]);
                    foreach ($expenseSetting->carrierGroups as $carrierGroup) {
                        $expenseSetting->carriers()->saveMany($carrierGroup->carriers);
                    }
                }
            }
        });

        return redirect()->route('carrier-group.index')->with('success', __('Carrier group updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $carrierGroup = CarrierGroup::find($id);
        foreach ($carrierGroup->carriers as $carrier) {
            $carrier->update([
                'carrier_group_id' => null
            ]);

        }

        $carrierGroup->users()->sync([]);

        $carrierGroup->delete();

        return redirect()->route('carrier-group.index')->with('success', __('Carrier group deleted successfully'));
    }
}
