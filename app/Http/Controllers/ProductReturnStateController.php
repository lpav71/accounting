<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductReturnStateRequest;
use App\OrderDetailState;
use App\ProductReturnState;
use Illuminate\Http\Response;

class ProductReturnStateController extends Controller
{
    //TODO Надо пересмотреть всю систему разрешений к более общим
    public function __construct()
    {
        $this->middleware('permission:orderDetailState-list');
        $this->middleware('permission:orderDetailState-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:orderDetailState-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список статусов возвратов
     *
     * @return Response
     */
    public function index()
    {
        $productReturnStates = ProductReturnState::orderBy('id', 'DESC')->paginate(15);

        return view('product-return-states.index', compact('productReturnStates'));
    }

    /**
     * Отображение формы для создания нового статуса
     *
     * @return Response
     */
    public function create()
    {
        $returnStates = ProductReturnState::all()->pluck('name', 'id');
        $orderDetailStates = OrderDetailState::all()->pluck('name', 'id');

        return view('product-return-states.create', compact('returnStates', 'orderDetailStates'));
    }

    /**
     * Сохранение данных из формы создания нового статуса
     *
     * @param  ProductReturnStateRequest $request
     * @return Response
     */
    public function store(ProductReturnStateRequest $request)
    {
        $data = $request->input();

        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['inactive_return'] = isset($data['inactive_return']) ? $data['inactive_return'] : 0;
        $data['shipment_available'] = isset($data['shipment_available']) ? $data['shipment_available'] : 0;

        $productReturnState = ProductReturnState::create($data);
        $productReturnState->previousStates()->sync($request->previous_states_id);
        $productReturnState->needOrderDetailStates()->sync($request->need_order_detail_state_id);
        $productReturnState->needOneOrderDetailStates()->sync($request->need_one_order_detail_state_id);

        return redirect()
            ->route('product-return-states.index')
            ->with('success', __('Product Return State created successfully'));
    }

    /**
     * Отображение формы редактирования статуса
     *
     * @param  \App\ProductReturnState $productReturnState
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductReturnState $productReturnState)
    {
        $returnStates = ProductReturnState::all()->pluck('name', 'id');
        $orderDetailStates = OrderDetailState::all()->pluck('name', 'id');

        return view(
            'product-return-states.edit',
            compact(
                'productReturnState',
                'returnStates',
                'orderDetailStates'
            )
        );
    }

    /**
     * Сохранение данных из формы редактирования статуса
     *
     * @param  ProductReturnStateRequest $request
     * @param  \App\ProductReturnState $productReturnState
     * @return \Illuminate\Http\Response
     */
    public function update(ProductReturnStateRequest $request, ProductReturnState $productReturnState)
    {
        $data = $request->input();

        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['inactive_return'] = isset($data['inactive_return']) ? $data['inactive_return'] : 0;
        $data['shipment_available'] = isset($data['shipment_available']) ? $data['shipment_available'] : 0;

        $productReturnState->update($data);
        $productReturnState->previousStates()->sync($request->previous_states_id);
        $productReturnState->needOrderDetailStates()->sync($request->need_order_detail_state_id);
        $productReturnState->needOneOrderDetailStates()->sync($request->need_one_order_detail_state_id);

        return redirect()
            ->route('product-return-states.index')
            ->with('success', __('Product Return State updated successfully'));
    }
}
