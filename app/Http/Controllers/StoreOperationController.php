<?php

namespace App\Http\Controllers;

use App\Operation;
use App\Order;
use App\Product;
use App\Services\SecurityService\SecurityService;
use App\Store;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use App\Exceptions\DoingException;
use Illuminate\Validation\ValidationException;
use DB;

class StoreOperationController extends Controller
{
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->middleware('permission:store-operation');
    }

    /**
     * Отображение формы создания операции
     *
     * @param  \App\Store $store
     * @return \Illuminate\Http\Response
     */
    public function create(Store $store)
    {
        if (!$store->users()->find(Auth::id())) {
            throw UnauthorizedException::forPermissions([]);
        }
        $products = Product::all()->pluck('name', 'id');
        $orders = Order::all()->pluck('id', 'id')->prepend(__('No'), 0);

        return view('store-operations.create', compact('store', 'products', 'orders'));
    }

    /**
     * Сохранение данных из формы создания операции
     *
     * @param Request $request
     * @param Store $store
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function store(Request $request, Store $store)
    {
        if (!$store->users()->find(Auth::id())) {
            throw UnauthorizedException::forPermissions([]);
        }
        $this->validate($request, [
            'product_id' => 'required',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:C,D',
            'comment' => 'required|string|min:10',
            'order_id' => 'integer|min:0',
        ]);

        try {

           $operation = Operation::create(
                array_merge(
                    $request->input(),
                    [
                        'user_id' => Auth::id(),
                        'order_detail_id' => 0,
                        'is_reservation' => strstr('R', $request->type) ? 1 : 0,
                        'operable_type' => Product::class,
                        'operable_id' => $request->product_id,
                        'storage_type' => Store::class,
                        'storage_id' => $store->id,
                    ]
                )
            );

           if($operation->type == "C") {
               $service = new SecurityService();
               $service->operationWithoutOrderNumber($operation);
           }

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            }

            throw $exception;

        }


        /**
         * @var User $user
         */
        $user = Auth::user();

        return redirect()->route(
            $user->hasPermissionTo('store-list') ? 'stores.show' : 'own-stores.show',
            ['store' => $store]);
    }
}
