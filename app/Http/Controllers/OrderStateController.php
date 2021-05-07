<?php

namespace App\Http\Controllers;

use App\OrderState;
use App\OrderDetailState;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderStateController extends Controller
{
    /**
     * OrderStateController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:orderState-list');
        $this->middleware('permission:orderState-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:orderState-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:orderState-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orderStates = OrderState::orderBy('id', 'DESC')->paginate(50);

        return view('order-states.index', compact('orderStates'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $orderStates = OrderState::all()->pluck('name', 'id');
        $orderDetailStates = OrderDetailState::all()->pluck('full_name', 'id');
        $roles = Role::all()->pluck('name', 'id');

        return view('order-states.create', compact('orderStates', 'orderDetailStates', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:order_states,name',
            'new_order_detail_state_id' => 'required|integer|min:0',
            'previous_order_states_id' => 'array',
            'need_order_detail_state_id' => 'array',
            'need_order_detail_state_id.*' => 'integer|min:1',
            'need_one_order_detail_state_id' => 'array',
            'need_one_order_detail_state_id.*' => 'integer|min:1',
            'check_payment' => 'required|integer|in:0,1',
            'color' => 'regex:/^#[0-9ABCDEFabcdef]{6}$/',
            'is_blocked_edit_order_details' => 'integer|in:0,1|nullable',
            'is_sent' => 'integer|in:0,1|nullable',
            'is_confirmed' => 'integer|in:0,1|nullable',
            'check_certificates_number' => 'integer|in:0,1|nullable',
            'roles' => 'array'
        ]);

        $data = $request->input();

        $data['need_order_detail_state_id'] = isset($data['need_order_detail_state_id']) ? $data['need_order_detail_state_id'] : [];
        $data['need_one_order_detail_state_id'] = isset($data['need_one_order_detail_state_id']) ? $data['need_one_order_detail_state_id'] : [];
        $data['is_blocked_edit_order_details'] = isset($data['is_blocked_edit_order_details']) ? $data['is_blocked_edit_order_details'] : 0;
        $data['is_sent'] = isset($data['is_sent']) ? $data['is_sent'] : 0;
        $data['is_new'] = isset($data['is_new']) ? $data['is_sent'] : 0;
        $data['is_confirmed'] = isset($data['is_confirmed']) ? $data['is_confirmed'] : 0;
        $data['cdek_not_load'] = isset($data['cdek_not_load']) ? $data['cdek_not_load'] : 0;
        $data['inactive_order'] = isset($data['inactive_order']) ? $data['inactive_order'] : 0;
        $data['check_certificates_number'] = isset($data['check_certificates_number']) ? $data['check_certificates_number'] : 0;
        $data['shipment_available'] = isset($data['shipment_available']) ? $data['shipment_available'] : 0;
        $data['roles'] = isset($data['roles']) ? $data['roles'] : [];

        if ($request->is_sending_external_data) {
            $data = [
                'name' => $request->name,
                'previous_order_states_id' => $request->previous_order_states_id,
                'is_sending_external_data' => $request->is_sending_external_data,
                'need_order_detail_state_id' => [],
                'need_one_order_detail_state_id' => [],
                'roles' => [],
                'new_order_detail_state_id' => 0,
                'check_payment' => 0,
                'color' => $request->color,
            ];
        }

        $orderState = OrderState::create($data);
        $orderState->previousStates()->sync($data['previous_order_states_id']);
        $orderState->needOrderDetailStates()->sync($data['need_order_detail_state_id']);
        $orderState->needOneOrderDetailStates()->sync($data['need_one_order_detail_state_id']);
        $orderState->roles()->sync($data['roles']);

        return redirect()->route('order-states.index')->with('success', 'Order State created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\OrderState $orderState
     * @return \Illuminate\Http\Response
     */
    public function show(OrderState $orderState)
    {
        return view('order-states.show', compact('orderState'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\OrderState $orderState
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderState $orderState)
    {
        $orderDetailStates = OrderDetailState::all()->pluck('full_name', 'id');
        $orderStates = OrderState::all()->pluck('name', 'id');

        return view('order-states.edit', compact('orderState', 'orderStates', 'orderDetailStates'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\OrderState $orderState
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderState $orderState)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('order_states', 'name')->ignore($orderState->id),
            ],
            'new_order_detail_state_id' => 'required|integer|min:0',
            'previous_order_states_id' => 'array',
            'need_order_detail_state_id' => 'array',
            'need_order_detail_state_id.*' => 'integer|min:1',
            'need_one_order_detail_state_id' => 'array',
            'need_one_order_detail_state_id.*' => 'integer|min:1',
            'check_payment' => 'required|integer|in:0,1',
            'check_carrier' => 'required|integer|in:0,1',
            'color' => 'regex:/^#[0-9ABCDEFabcdef]{6}$/',
            'is_blocked_edit_order_details' => 'integer|in:0,1|nullable',
            'is_sent' => 'integer|in:0,1|nullable',
            'is_confirmed' => 'integer|in:0,1|nullable',
            'check_certificates_number' => 'integer|in:0,1|nullable',
            'roles' => 'array',
        ]);

        $data = $request->input();

        $data['need_order_detail_state_id'] = isset($data['need_order_detail_state_id']) ? $data['need_order_detail_state_id'] : [];
        $data['need_one_order_detail_state_id'] = isset($data['need_one_order_detail_state_id']) ? $data['need_one_order_detail_state_id'] : [];
        $data['is_blocked_edit_order_details'] = isset($data['is_blocked_edit_order_details']) ? $data['is_blocked_edit_order_details'] : 0;
        $data['previous_order_states_id'] = isset($data['previous_order_states_id']) ? $data['previous_order_states_id'] : 0;
        $data['is_sent'] = isset($data['is_sent']) ? $data['is_sent'] : 0;
        $data['is_new'] = isset($data['is_new']) ? $data['is_new'] : 0;
        $data['is_confirmed'] = isset($data['is_confirmed']) ? $data['is_confirmed'] : 0;
        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['cdek_not_load'] = isset($data['cdek_not_load']) ? $data['cdek_not_load'] : 0;
        $data['inactive_order'] = isset($data['inactive_order']) ? $data['inactive_order'] : 0;
        $data['check_certificates_number'] = isset($data['check_certificates_number']) ? $data['check_certificates_number'] : 0;
        $data['shipment_available'] = isset($data['shipment_available']) ? $data['shipment_available'] : 0;
        $roles = isset($data['roles']) ? $data['roles'] : [];

        if ($request->is_sending_external_data) {
            $data = [
                'name' => $request->name,
                'previous_order_states_id' => $request->previous_order_states_id,
                'is_sending_external_data' => $request->is_sending_external_data,
                'need_order_detail_state_id' => [],
                'need_one_order_detail_state_id' => [],
                'roles' => [],
                'new_order_detail_state_id' => 0,
                'check_payment' => 0,
                'check_carrier' => 0,
                'color' => $request->color,
            ];
        } else {
            $data['is_sending_external_data'] = 0;
        }

        $orderState->update($data);
        $orderState->previousStates()->sync($data['previous_order_states_id']);
        $orderState->needOrderDetailStates()->sync($data['need_order_detail_state_id']);
        $orderState->needOneOrderDetailStates()->sync($data['need_one_order_detail_state_id']);
        $orderState->roles()->sync($roles);

        return redirect()->route('order-states.index')->with('success', 'Order State updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param OrderState $orderState
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(OrderState $orderState)
    {
        if ($orderState->orders()->count()) {
            return redirect()->route('order-states.index')->with('warning', 'This State can not be deleted, because it\'s part of orders: '.implode(', ', $orderState->orders()->distinct()->pluck('order_id')->toArray()));
        }
        $orderState->delete();

        return redirect()->route('order-states.index')->with('success', 'Order State deleted successfully');
    }
}
