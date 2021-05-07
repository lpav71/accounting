<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\Exceptions\DoingException;
use App\Http\Requests\ProductReturnRequest;
use App\Order;
use App\OrderDetail;
use App\OrderDetailState;
use App\Product;
use App\ProductReturn;
use App\ProductReturnState;
use App\Store;
use DB;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ProductReturnController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:order-list');
        $this->middleware('permission:order-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:order-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список возвратов
     *
     * @return Response
     */
    public function index()
    {
        $productReturns = ProductReturn::orderBy('id', 'desc')->paginate(50);

        return view('product-returns.index', compact('productReturns'));
    }

    /**
     * Отображение формы для создания нового возврата
     *
     * @param Order $order
     * @return Response
     */
    public function create(Order $order)
    {

        $states = ProductReturnState::all()->filter(
            function (ProductReturnState $productReturnState) {
                return $productReturnState->previousStates->isEmpty();
            }
        )->pluck('name', 'id');

        $stores = Store::all()->pluck('name', 'id');

        $carriers = Carrier::all()->pluck('name', 'id')->prepend(__('Unknown'), '0');

        $availableOrderDetails = $order
            ->orderDetails
            ->filter(
                function (OrderDetail $orderDetail) use ($order) {
                    return $order
                            ->getFreeVirtualQuantity(
                                $orderDetail->product_id,
                                Product::class
                            ) > 0;
                }
            );

        return view(
            'product-returns.create',
            compact(
                'order',
                'states',
                'availableOrderDetails',
                'stores',
                'carriers'
            )
        );
    }

    /**
     * Сохранение данных из формы создания нового возврата
     *
     * @param  ProductReturnRequest $request
     * @return Response
     * @throws \Exception
     */
    public function store(ProductReturnRequest $request)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $productReturn = ProductReturn::create($request->input());

            $orderDetails = collect($request->order_detail);

            $orderDetails->each(
                function ($orderDetailArray, $orderDetailId) use ($productReturn) {

                    $orderDetail = OrderDetail::find($orderDetailId);

                    $orderDetail->update(
                        array_merge(
                            $orderDetailArray,
                            [
                                'product_return_id' => $productReturn->id,
                            ]
                        )
                    );

                    $orderDetail->states()->save(OrderDetailState::find($orderDetailArray['order_detail_state_id']));
                }
            );

            $productReturn->states()->save(ProductReturnState::find($request->product_return_state_id));


        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }


        DB::commit();

        return redirect()->route('product-returns.index')->with('success', __('Product return created successfully'));
    }

    /**
     * Отображение формы редактирования возврата
     *
     * @param  ProductReturn $productReturn
     * @return Response
     */
    public function edit(ProductReturn $productReturn)
    {
        $states = ProductReturnState::all()
            ->filter(
                function (ProductReturnState $productReturnState) use ($productReturn) {
                    return $productReturnState
                        ->previousStates()
                        ->where('product_return_states.id', $productReturn->currentState()->id)
                        ->count();
                }
            )
            ->pluck('name', 'id')
            ->prepend($productReturn->currentState()->name, $productReturn->currentState()->id);

        $stores = Store::all()->pluck('name', 'id');

        $carriers = Carrier::all()->pluck('name', 'id')->prepend(__('Unknown'), '0');

        $orderDetails = $productReturn->orderDetails;

        $order = $productReturn->order;
        
        $cashboxOperations = $order->operations->where('storage_type', 'App\Cashbox');

        return view(
            'product-returns.edit',
            compact(
                'productReturn',
                'states',
                'stores',
                'carriers',
                'orderDetails',
                'order',
                'cashboxOperations'    
            )
        );
    }

    /**
     * Сохранение данных из формы редактирования возврата
     *
     * @param  ProductReturnRequest $request
     * @param  ProductReturn $productReturn
     * @return Response
     * @throws \Exception
     */
    public function update(ProductReturnRequest $request, ProductReturn $productReturn)
    {

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $productReturn->update($request->input());

            $orderDetails = collect($request->order_detail);

            $orderDetails->each(
                function ($orderDetailArray, $orderDetailId) use ($productReturn) {

                    $orderDetail = OrderDetail::find($orderDetailId);

                    $orderDetail->update(
                        array_merge(
                            $orderDetailArray,
                            [
                                'product_return_id' => $productReturn->id,
                            ]
                        )
                    );

                    $orderDetail->states()->save(OrderDetailState::find($orderDetailArray['order_detail_state_id']));
                }
            );

            $productReturn->states()->save(ProductReturnState::find($request->product_return_state_id));


        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }

        DB::commit();

        return redirect()->route('product-returns.edit', $productReturn)->with('success', __('Product return updated successfully'));
    }
}
