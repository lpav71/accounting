<?php

namespace App\Http\Controllers;

use App\Http\Requests\RouteListStateRequest;
use App\OrderDetailState;
use App\OrderState;
use App\ProductExchangeState;
use App\ProductReturnState;
use App\RouteListState;
use Illuminate\Http\Response;

class RouteListStateController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:orderState-list');
        $this->middleware('permission:orderState-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:orderState-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список статусов маршрутных листов
     *
     * @return Response
     */
    public function index()
    {
        $routeListStates = RouteListState::orderBy('id', 'DESC')->paginate(15);

        return view('route-list-states.index', compact('routeListStates'));
    }

    /**
     * Отображение формы для создания нового статуса
     *
     * @return Response
     */
    public function create()
    {
        $routeListStates = RouteListState::all()->pluck('name', 'id');
        $orderStates = OrderState::all()->pluck('name', 'id');
        $productReturnStates = ProductReturnState::all()->pluck('name', 'id');
        $productExchangeStates = ProductExchangeState::all()->pluck('name', 'id');
        $orderDetailStates = OrderDetailState::all()->pluck('full_name', 'id');

        return view(
            'route-list-states.create',
            compact(
                'routeListStates',
                'orderStates',
                'productReturnStates',
                'productExchangeStates',
                'orderDetailStates'
            )
        );
    }

    /**
     * Сохранение данных из формы создания нового статуса
     *
     * @param  RouteListStateRequest $request
     * @return Response
     */
    public function store(RouteListStateRequest $request)
    {
        $routeListState = RouteListState::create($request->input());
        $routeListState->previousStates()->sync($request->previous_states_id);
        $routeListState->needOrderStates()->sync($request->need_order_state_id);
        $routeListState->needProductReturnStates()->sync($request->need_product_return_state_id);
        $routeListState->needProductExchangeStates()->sync($request->need_product_exchange_state_id);
        $routeListState->needOrderDetailStates()->sync($request->need_order_detail_state_id);

        foreach ($request->new_order_states as $currentOrderStateId => $newOrderStateId) {
            $routeListState->updateNewOrderState($currentOrderStateId, $newOrderStateId);
        }

        foreach ($request->new_product_return_states as $currentProductReturnStateId => $newProductReturnStateId) {
            $routeListState->updateNewProductReturnState($currentProductReturnStateId, $newProductReturnStateId);
        }

        foreach ($request->new_product_exchange_states as $currentProductExchangeStateId => $newProductExchangeStateId) {
            $routeListState->updateNewProductExchangeState($currentProductExchangeStateId, $newProductExchangeStateId);
        }

        foreach ($request->new_order_detail_states as $currentOrderDetailStateId => $newOrderDetailStateId) {
            $routeListState->updateNewOrderDetailState($currentOrderDetailStateId, $newOrderDetailStateId);
        }

        return redirect()
            ->route('route-list-states.index')
            ->with('success', __('Route List State created successfully'));
    }

    /**
     * Отображение формы редактирования статуса
     *
     * @param  RouteListState $routeListState
     * @return Response
     */
    public function edit(RouteListState $routeListState)
    {
        $routeListStates = RouteListState::all()->pluck('name', 'id');
        $orderStates = OrderState::all()->pluck('name', 'id');
        $productReturnStates = ProductReturnState::all()->pluck('name', 'id');
        $productExchangeStates = ProductExchangeState::all()->pluck('name', 'id');
        $orderDetailStates = OrderDetailState::all()->pluck('full_name', 'id');

        return view(
            'route-list-states.edit',
            compact(
                'routeListStates',
                'orderStates',
                'productReturnStates',
                'productExchangeStates',
                'orderDetailStates',
                'routeListState'
            )
        );
    }

    /**
     * Сохранение данных из формы редактирования статуса
     *
     * @param  RouteListStateRequest $request
     * @param  RouteListState $routeListState
     * @return Response
     */
    public function update(RouteListStateRequest $request, RouteListState $routeListState)
    {
        $routeListState->update($request->input());
//        $routeListState->previousStates()->sync($request->previous_states_id);
        $routeListState->needOrderStates()->sync($request->need_order_state_id);
        $routeListState->needProductReturnStates()->sync($request->need_product_return_state_id);
        $routeListState->needProductExchangeStates()->sync($request->need_product_exchange_state_id);
        $routeListState->needOrderDetailStates()->sync($request->need_order_detail_state_id);

        foreach ($request->new_order_states as $currentOrderStateId => $newOrderStateId) {
            $routeListState->updateNewOrderState($currentOrderStateId, $newOrderStateId);
        }

        foreach ($request->new_product_return_states as $currentProductReturnStateId => $newProductReturnStateId) {
            $routeListState->updateNewProductReturnState($currentProductReturnStateId, $newProductReturnStateId);
        }

        foreach ($request->new_product_exchange_states as $currentProductExchangeStateId => $newProductExchangeStateId) {
            $routeListState->updateNewProductExchangeState($currentProductExchangeStateId, $newProductExchangeStateId);
        }

        foreach ($request->new_order_detail_states as $currentOrderDetailStateId => $newOrderDetailStateId) {
            $routeListState->updateNewOrderDetailState($currentOrderDetailStateId, $newOrderDetailStateId);
        }

        return redirect()
            ->route('route-list-states.index')
            ->with('success', __('Route List State updated successfully'));
    }
}
