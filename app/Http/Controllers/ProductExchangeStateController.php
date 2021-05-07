<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductExchangeStateRequest;
use App\OrderDetailState;
use App\ProductExchangeState;
use Illuminate\Http\Response;

class ProductExchangeStateController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:orderDetailState-list');
        $this->middleware('permission:orderDetailState-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:orderDetailState-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список статусов обменов
     *
     * @return Response
     */
    public function index()
    {
        $productExchangeStates = ProductExchangeState::orderBy('id', 'DESC')->paginate(15);

        return view('product-exchange-states.index', compact('productExchangeStates'));
    }

    /**
     * Отображение формы для создания нового статуса
     *
     * @return Response
     */
    public function create()
    {
        $exchangeStates = ProductExchangeState::all()->pluck('name', 'id');
        $orderDetailStates = OrderDetailState::all()->pluck('name', 'id');

        return view('product-exchange-states.create', compact('exchangeStates', 'orderDetailStates'));
    }

    /**
     * Сохранение данных из формы создания нового статуса
     *
     * @param  ProductExchangeStateRequest $request
     * @return Response
     */
    public function store(ProductExchangeStateRequest $request)
    {
        $data = $request->input();

        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['is_sent'] = isset($data['is_sent']) ? $data['is_sent'] : 0;
        $data['next_auto_closing_status'] = isset($data['next_auto_closing_status']) ? $data['next_auto_closing_status'] : 0;
        $data['shipment_available'] = isset($data['shipment_available']) ? $data['shipment_available'] : 0;
        $data['inactive_exchange'] = isset($data['inactive_exchange']) ? $data['inactive_exchange'] : 0;

        $productExchangeState = ProductExchangeState::create($data);
        $productExchangeState->previousStates()->sync($request->previous_states_id);
        $productExchangeState->needOrderDetailStates()->sync($request->need_order_detail_state_id);
        $productExchangeState->needOneOrderDetailStates()->sync($request->need_one_order_detail_state_id);
        $productExchangeState->needExchangeOrderDetailStates()->sync($request->need_exchange_order_detail_state_id);
        $productExchangeState->needOneExchangeOrderDetailStates()->sync($request->need_one_exchange_order_detail_state_id);

        return redirect()
            ->route('product-exchange-states.index')
            ->with('success', __('Product Exchange State created successfully'));
    }

    /**
     * Отображение формы редактирования статуса
     *
     * @param  ProductExchangeState $productExchangeState
     * @return Response
     */
    public function edit(ProductExchangeState $productExchangeState)
    {
        $exchangeStates = ProductExchangeState::all()->pluck('name', 'id');
        $orderDetailStates = OrderDetailState::all()->pluck('name', 'id');

        return view(
            'product-exchange-states.edit',
            compact(
                'productExchangeState',
                'exchangeStates',
                'orderDetailStates'
            )
        );
    }

    /**
     * Сохранение данных из формы редактирования статуса
     *
     * @param  ProductExchangeStateRequest $request
     * @param  ProductExchangeState $productExchangeState
     * @return Response
     */
    public function update(ProductExchangeStateRequest $request, ProductExchangeState $productExchangeState)
    {
        $data = $request->input();

        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['is_sent'] = isset($data['is_sent']) ? $data['is_sent'] : 0;
        $data['next_auto_closing_status'] = isset($data['next_auto_closing_status']) ? $data['next_auto_closing_status'] : 0;
        $data['shipment_available'] = isset($data['shipment_available']) ? $data['shipment_available'] : 0;
        $data['inactive_exchange'] = isset($data['inactive_exchange']) ? $data['inactive_exchange'] : 0;

        $productExchangeState->update($data);
        $productExchangeState->previousStates()->sync($request->previous_states_id);
        $productExchangeState->needOrderDetailStates()->sync($request->need_order_detail_state_id);
        $productExchangeState->needOneOrderDetailStates()->sync($request->need_one_order_detail_state_id);
        $productExchangeState->needExchangeOrderDetailStates()->sync($request->need_exchange_order_detail_state_id);
        $productExchangeState->needOneExchangeOrderDetailStates()->sync($request->need_one_exchange_order_detail_state_id);

        return redirect()
            ->route('product-exchange-states.index')
            ->with('success', __('Product Exchange State updated successfully'));
    }
}
