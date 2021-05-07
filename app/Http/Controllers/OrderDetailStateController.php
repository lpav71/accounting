<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderDetailStateRequest;
use App\Operation;
use App\OrderDetail;
use App\OrderDetailState;
use App\VirtualOperation;
use Illuminate\Http\Response;

class OrderDetailStateController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:orderDetailState-list');
        $this->middleware('permission:orderDetailState-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:orderDetailState-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:orderDetailState-delete', ['only' => ['destroy']]);
    }

    /**
     * Отображает список статусов товарных позиций
     *
     * @return Response
     */
    public function index()
    {
        $orderDetailStates = OrderDetailState::orderBy('id', 'DESC')->paginate(50);

        return view('order-detail-states.index', compact('orderDetailStates'));
    }

    /**
     * Отображение формы для создания нового статуса
     *
     * @return Response
     */
    public function create()
    {
        $orderDetailStates = OrderDetailState::all()->pluck('full_name', 'id');

        $storeOperations = collect(Operation::STORE_ORDER_OPERATIONS)
            ->map(function ($name, $id) {
                return __($name);
            });

        $currencyOperations = collect(VirtualOperation::OPERATION_TYPES_CURRENCY_BY_ORDER)
            ->map(function ($name, $id) {
                return __($name);
            });

        $productOperations = collect(VirtualOperation::OPERATION_TYPES_PRODUCT_BY_ORDER)
            ->map(function ($name, $id) {
                return __($name);
            });

        $stateOwners = collect(OrderDetailState::OWNERS)
            ->map(function ($name, $id) {
                return __($name);
            });

        $orderDetailOwners = collect(OrderDetail::OWNERS)
            ->map(function ($name, $id) {
                return __($name);
            })
            ->prepend(__('Not change'), 'not');

        return view(
            'order-detail-states.create',
            compact(
                'orderDetailStates',
                'storeOperations',
                'currencyOperations',
                'productOperations',
                'stateOwners',
                'orderDetailOwners'
            )
        );
    }

    /**
     * Сохранение данных из формы создания нового статуса
     *
     * @param  OrderDetailStateRequest $request
     * @return Response
     */
    public function store(OrderDetailStateRequest $request)
    {
        $orderDetailState = OrderDetailState::create($request->input());
        $orderDetailState->previousStates()->sync($request->previous_order_detail_states_id);

        return redirect()
            ->route('order-detail-states.index')
            ->with('success', __('Order Detail State created successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\OrderDetailState $orderDetailState
     * @return \Illuminate\Http\Response
     */
    public function show(OrderDetailState $orderDetailState)
    {
        return view('order-detail-states.show', compact('orderDetailState'));
    }

    /**
     * Отображение формы редактирования статуса
     *
     * @param  OrderDetailState $orderDetailState
     * @return Response
     */
    public function edit(OrderDetailState $orderDetailState)
    {
        $orderDetailStates = OrderDetailState::all()
            ->whereNotIn('id', $orderDetailState->id)
            ->pluck('full_name', 'id');

        $storeOperations = collect(Operation::STORE_ORDER_OPERATIONS)
            ->map(function ($name, $id) {
                return __($name);
            });

        $currencyOperations = collect(VirtualOperation::OPERATION_TYPES_CURRENCY_BY_ORDER)
            ->map(function ($name, $id) {
                return __($name);
            });

        $productOperations = collect(VirtualOperation::OPERATION_TYPES_PRODUCT_BY_ORDER)
            ->map(function ($name, $id) {
                return __($name);
            });

        $stateOwners = collect(OrderDetailState::OWNERS)
            ->map(function ($name, $id) {
                return __($name);
            });

        $orderDetailOwners = collect(OrderDetail::OWNERS)
            ->map(function ($name, $id) {
                return __($name);
            })
            ->prepend(__('Not change'), 'not');

        return view(
            'order-detail-states.edit',
            compact(
                'orderDetailState',
                'orderDetailStates',
                'storeOperations',
                'currencyOperations',
                'productOperations',
                'stateOwners',
                'orderDetailOwners'
            )
        );
    }

    /**
     * Сохранение данных из формы редактирования статуса
     *
     * @param  OrderDetailStateRequest $request
     * @param  OrderDetailState $orderDetailState
     * @return Response
     */
    public function update(OrderDetailStateRequest $request, OrderDetailState $orderDetailState)
    {
        $data = $request->input();
        $data['is_hidden'] = isset($data['is_hidden']) ? $data['is_hidden'] : 0;
        $data['need_payment'] = isset($data['need_payment']) ? $data['need_payment'] : 0;
        $data['is_block_editing_order_detail'] = isset($data['is_block_editing_order_detail']) ? $data['is_block_editing_order_detail'] : 0;
        $data['is_block_deleting_order_detail'] = isset($data['is_block_deleting_order_detail']) ? $data['is_block_deleting_order_detail'] : 0;
        $data['is_delivered'] = isset($data['is_delivered']) ? $data['is_delivered'] : 0;
        $data['crediting_certificate'] = isset($data['crediting_certificate']) ? $data['crediting_certificate'] : 0;
        $data['is_courier_state'] = isset($data['is_courier_state']) ? $data['is_courier_state'] : 0;
        $data['is_returned'] = isset($data['is_returned']) ? $data['is_returned'] : 0;
        $data['is_new'] = isset($data['is_new']) ? $data['is_new'] : 0;
        $data['is_sent'] = isset($data['is_sent']) ? $data['is_sent'] : 0;
        $data['is_reserved'] = isset($data['is_reserved']) ? $data['is_reserved'] : 0;
        $data['is_shipped'] = isset($data['is_shipped']) ? $data['is_shipped'] : 0;
        $data['writing_off_certificate'] = isset($data['writing_off_certificate']) ? $data['writing_off_certificate'] : 0;
        $data['zeroing_certificate_number'] = isset($data['zeroing_certificate_number']) ? $data['zeroing_certificate_number'] : 0;

        $orderDetailState->update($data);
        $orderDetailState->previousStates()->sync($request->previous_order_detail_states_id);

        return redirect()
            ->route('order-detail-states.index')
            ->with('success', __('Order Detail State updated successfully'));
    }

    /**
     * @param OrderDetailState $orderDetailState
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(OrderDetailState $orderDetailState)
    {
        if ($orderDetailState->orderDetails()->count()) {
            return redirect()->route('order-detail-states.index')->with('warning',
                'This State can not be deleted, because it\'s part of orders.');
        }
        if ($orderDetailState->nextStates()->count()) {
            return redirect()->route('order-detail-states.index')->with('warning',
                'This State can not be deleted, because it\'s part of states.');
        }
        $orderDetailState->delete();

        return redirect()->route('order-detail-states.index')->with('success',
            'Order Detail State deleted successfully');
    }
}
