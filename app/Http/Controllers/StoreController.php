<?php

namespace App\Http\Controllers;

use App\Exceptions\DoingException;
use App\Filters\OperationFilter;
use App\Http\Requests\CsvImportRequest;
use App\Http\Requests\CsvTransferRequest;
use App\Operation;
use App\Order;
use App\OrderDetail;
use App\OrderDetailState;
use App\Product;
use App\ProductExchange;
use App\ProductReturn;
use App\Store;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Maatwebsite\Excel\Readers\LaravelExcelReader;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Spatie\Permission\Exceptions\UnauthorizedException;


class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:store-list', ['only' => ['index']]);
        $this->middleware('permission:store-show-own', ['except' => ['index', 'create', 'store', 'edit', 'update', 'destroy']]);
        $this->middleware('permission:store-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:store-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:store-delete', ['only' => ['destroy']]);
    }

    /**
     * Отображение списка складов
     *
     * @return Response
     */
    public function index(Store $store, Request $request)
    {
        $stores = $store->sortable(['name' => 'asc'])
        ->paginate(50)
        ->appends($request->query());

        return view('stores.index', compact('stores'));
    }

    /**
     * Отображение формы создания нового склада
     *
     * @return Response
     */
    public function create()
    {
        $users = User::pluck('name', 'id');

        return view('stores.create', compact('users'));
    }

    /**
     * Сохранение данных из формы создания нового склада
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|unique:stores,name',
                'user_id' => 'array',
                'user_id_with_operation_rights' => 'array',
                'user_id_with_reservation_rights' => 'array',
                'user_id_with_transfer_rights' => 'array',
                'limit' => 'integer'
            ]
        );

        $store = Store::create($request->input());
        $store->users()->sync($request->user_id);
        $store->usersWithOperationRights()->sync($request->user_id_with_operation_rights);
        $store->usersWithReservationRights()->sync($request->user_id_with_reservation_rights);
        $store->usersWithTransferRights()->sync($request->user_id_with_transfer_rights);

        return redirect()
            ->route('stores.index')
            ->with('success', __('Store created successfully'));
    }

    /**
     * Просмотр склада
     *
     * @param Store $store
     * @param OperationFilter $filters
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Store $store, OperationFilter $filters, Request $request)
    {
        $operations = Operation::query()
            ->where('storage_type', Store::class)
            ->where('storage_id', $store->id)
            ->filter($filters)
            ->orderBy('id', 'DESC')
            ->paginate(25)
            ->appends($request->query());

        //Если нет операций проверяем композитный товар
        if(!count($operations) && $request->operableId) {
            $product = Product::find($request->operableId);
            if($product->isComposite()) {
                $products = $product->products()->pluck('id');
                $operations = Operation::query()
                    ->where('storage_type', Store::class)
                    ->where('storage_id', $store->id)
                    ->whereIn('operable_id', $products)
                    ->orderBy('id', 'DESC')
                    ->paginate(25)
                    ->appends($request->query());
            }
        }

        return view('stores.show', compact('store', 'operations'));
    }

    /**
     * Просмотр собственного склада
     *
     * @param Store $store
     * @param OperationFilter $filters
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showOwn(Store $store, OperationFilter $filters, Request $request)
    {
        if (!$store->users()->find(Auth::id())) {
            throw UnauthorizedException::forPermissions([]);
        }

        return $this->show($store, $filters, $request);
    }

    /**
     * Просмотр текущих остатков склада
     *
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function currentProducts(Store $store)
    {
        $products = $this->paginate(
            $store->currentSimpleProducts(),
            50
        );

        return view('stores.current.products', compact('store', 'products'));
    }

    /**
     * Просмотр полного остатка по складу
     *
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function fullProducts(Store $store)
    {
        /**
         * @var $product Product
         */
        $products = $this->paginate(Product::all()->filter(function ($product) use ($store) {
            return $store->getRealCurrentQuantity($product->id) ?? $store->getRealCurrentQuantity($product->id);
        }), 50);

        return view('stores.full.products', compact('store', 'products'));
    }

    /**
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function fullProductsBrands(Store $store)
    {
        /**
         * @var $product Product
         */
        $products = Product::all();
        $manufacturers =[];
        foreach ($products as $product){
            if(!isset($manufacturers[$product->manufacturer->id])){
                $manufacturers[$product->manufacturer->id] = 0;
            }
            $manufacturers[$product->manufacturer->id] += $store->getRealCurrentQuantity($product->id) ?? 0;
        }
        return view('stores.current.products-brands', compact('store', 'manufacturers'));
    }

    /**
     * Отображение формы редактирования склада
     *
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Store $store)
    {
        $users = User::pluck('name', 'id');

        return view('stores.edit', compact('store', 'users'));
    }

    /**
     * Сохранение данных из формы редактирования склада
     *
     * @param Request $request
     * @param Store $store
     * @return RedirectResponse
     */
    public function update(Request $request, Store $store)
    {
        $this->validate(
            $request,
            [
                'name' => [
                    'required',
                    Rule::unique('stores', 'name')->ignore($store->id),
                ],
                'user_id' => 'array',
                'user_id_with_operation_rights' => 'array',
                'user_id_with_reservation_rights' => 'array',
                'user_id_with_transfer_rights' => 'array',
                'limit' => 'integer'
            ]
        );

        $store->update($request->input());
        $store->users()->sync($request->input('user_id'));
        $store->usersWithOperationRights()->sync($request->user_id_with_operation_rights);
        $store->usersWithReservationRights()->sync($request->user_id_with_reservation_rights);
        $store->usersWithTransferRights()->sync($request->user_id_with_transfer_rights);

        return redirect()->route('stores.index')->with('success', __('Store updated successfully'));
    }

    /**
     * Удаление склада
     *
     * @param Store $store
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Store $store)
    {
        if ($store->operations()->count()) {
            return redirect()->route('stores.index')->with(
                'warning',
                __('This Store can not be deleted, because it have operations.')
            );
        }

        if ($store->users()->count()) {
            return redirect()->route('stores.index')->with(
                'warning',
                __('This Store can not be deleted, because it have users.')
            );
        }

        $store->delete();

        return redirect()->route('stores.index')->with('success', 'Store deleted successfully');
    }

    /**
     * Отображение формы переноса товаров
     *
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function transfer(Store $store)
    {
        $products = Product::all();

        return view('stores.transfer', compact('store', 'products'));
    }

    /**
     * Перенос товаров
     *
     * @param Store $store
     * @param Request $request
     * @return RedirectResponse
     * @throws \Exception
     */
    public function transferProduct(Store $store, Request $request)
    {
        $this->validate(
            $request,
            [
                'product_id' => 'required',
                'quantity' => 'required|integer|min:1',
                'comment' => 'required|string|min:10',
                'store_id' => 'integer|min:0',
            ]
        );

        $this->validate(
            $request,
            ['quantity' => 'integer|max:'.Product::find($request->product_id)->getCombinedQuantity($store)],
            [
                'quantity.max' => __(
                    'The :attribute may not be greater than :max for Credit operation of current Product.'
                ),
            ]
        );

        $toStore = Store::find($request->store_id);

        $user = Auth::user();

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            Operation::create(
                array_merge(
                    $request->input(),
                    [
                        'type' => 'C',
                        'comment' => 'Перенос товара на склад '.$toStore->name.': '.$request->comment,
                        'user_id' => $user->id,
                        'order_id' => 0,
                        'order_detail_id' => 0,
                        'is_reservation' => 0,
                        'operable_type' => Product::class,
                        'operable_id' => $request->product_id,
                        'storage_type' => Store::class,
                        'storage_id' => $store->id,
                        'is_transfer' => 1,
                    ]
                )
            );

            Operation::create(
                array_merge(
                    $request->input(),
                    [
                        'type' => 'D',
                        'comment' => 'Перенос товара со склада '.$store->name.': '.$request->comment,
                        'user_id' => $user->id,
                        'order_id' => 0,
                        'order_detail_id' => 0,
                        'is_reservation' => 0,
                        'operable_type' => Product::class,
                        'operable_id' => $request->product_id,
                        'storage_type' => Store::class,
                        'storage_id' => $request->store_id,
                        'is_transfer' => 1,
                    ]
                )
            );

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            }

            throw $exception;

        }

        DB::commit();

        return redirect()->route('stores.transfer', compact('store'));
    }

    /**
     * Отображение формы множественного переноса товаров
     *
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function multiTransfer(Store $store)
    {

        $products = $store
            ->currentSimpleProducts()
            ->reduce(
                function (Collection $acc, Product $product) {
                    for ($i = 1; $i <= $product->currentQuantity; $i++) {
                        $acc->push(
                            collect(
                                [
                                    'name' => "{$product->id}.{$i}. {$product->name}",
                                    'id' => "{$i}-{$product->id}",
                                ]
                            )
                        );
                    }

                    return $acc;
                },
                collect([])
            )
            ->pluck('name', 'id');


        $stores = Store::all()
            ->filter(
                function (Store $storeItem) use ($store) {
                    return in_array(
                            Auth::id(),
                            $storeItem->usersWithTransferRights()->pluck('user_id')->toArray()
                        ) && $storeItem->id !== $store->id;
                }
            )->pluck('name', 'id');

        return view(
            'stores.transfer.multi',
            compact('store', 'products', 'stores')
        );
    }

    /**
     * Множественный перенос товаров
     *
     * @param Store $store
     * @param Request $request
     * @return RedirectResponse
     * @throws \Exception
     */
    public function multiTransferProducts(Store $store, Request $request)
    {
        $this->validate(
            $request,
            [
                'comment' => 'required|string|min:10',
                'store_id' => 'integer|min:0',
                'product_id' => 'array',
            ]
        );

        $data = $request->input();

        $data['product_id'] = isset($data['product_id']) ? $data['product_id'] : [];

        $user = Auth::user();

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $toStore = Store::findOrFail($request->store_id);

            foreach ($data['product_id'] as $productId) {

                $productId = preg_replace('/[0-9]+\-/', '', $productId);

                Operation::create(
                    array_merge(
                        [
                            'type' => 'C',
                            'comment' => 'Перенос товара на склад '.$toStore->name.': '.$request->comment,
                            'user_id' => $user->id,
                            'order_id' => 0,
                            'order_detail_id' => 0,
                            'is_reservation' => 0,
                            'operable_type' => Product::class,
                            'operable_id' => $productId,
                            'quantity' => 1,
                            'storage_type' => Store::class,
                            'storage_id' => $store->id,
                            'is_transfer' => 1,
                        ]
                    )
                );

                Operation::create(
                    array_merge(
                        [
                            'type' => 'D',
                            'comment' => 'Перенос товара со склада '.$store->name.': '.$request->comment,
                            'user_id' => $user->id,
                            'order_id' => 0,
                            'order_detail_id' => 0,
                            'is_reservation' => 0,
                            'operable_type' => Product::class,
                            'operable_id' => $productId,
                            'quantity' => 1,
                            'storage_type' => Store::class,
                            'storage_id' => $request->store_id,
                            'is_transfer' => 1,
                        ]
                    )
                );

            }

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            }

            throw $exception;

        }

        DB::commit();

        return redirect()->route('stores.show', compact('store'));
    }

    /**
     * Отображение формы выбора заказа при переносе товаров по заказу
     *
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function transferByOrderChooseOrder(Store $store)
    {
        $orders = Order::all()
            ->map(
                function (Order $order) {
                    return [
                        'number' => "{$order->getDisplayNumber()} [{$order->order_number}]",
                        'id' => $order->id,
                    ];
                }
            )
            ->pluck('number', 'id');

        $stores = Store::all()
            ->filter(
                function (Store $store) {
                    return in_array(
                        Auth::id(),
                        $store->usersWithTransferRights()->pluck('user_id')->toArray()
                    );
                }
            )->pluck('name', 'id');

        return view(
            'stores.transfer.by.order.choose.order',
            compact('store', 'orders', 'stores')
        );
    }

    /**
     * Отображение формы выбора товаров при переносе товаров по заказу
     *
     * @param Store $store
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function transferByOrderChooseProduct(Store $store, Request $request)
    {
        $this->validate(
            $request,
            [
                'comment' => 'required|string|min:10',
                'store_id' => 'integer|min:0',
                'order_id' => 'integer|min:0',
            ]
        );

        $order = Order::findOrFail($request->order_id);

        $comment = $request->comment;

        $toStore = Store::findOrFail($request->store_id);
        $orderDetails = $order
            ->orderDetails
            ->reduce(
                function ($acc, OrderDetail $orderDetail) {
                    if ($orderDetail->owner_type == ProductExchange::class
                        && !$orderDetail->is_exchange
                        || $orderDetail->owner_type == ProductReturn::class) {
                        return $acc;
                    }
                    $groupName = $orderDetail->owner_type == ProductExchange::class ? __('Exchanges') : __('Order');

                    $acc[$groupName][$orderDetail->id] = "{$orderDetail->product->name} [{$orderDetail->currentState()->name}] [{$orderDetail->store->name}]";

                    return $acc;
                },
                []
            );

        return view(
            'stores.transfer.by.order.choose.product',
            compact('store', 'order', 'toStore', 'orderDetails', 'comment')
        );
    }

    /**
     * Перенос товаров по заказу
     *
     * @param Store $store
     * @param Request $request
     * @return RedirectResponse
     * @throws \Exception
     */
    public function transferByOrderStore(Store $store, Request $request)
    {
        $this->validate(
            $request,
            [
                'comment' => 'required|string|min:10',
                'store_id' => 'integer|min:0',
                'order_id' => 'integer|min:0',
                'order_detail_id' => 'array',
            ]
        );

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $data = $request->input();

            $orderDetailIds = isset($data['order_detail_id']) ? $data['order_detail_id'] : [];

            $order = Order::findOrFail($request->order_id);

            $toStore = Store::findOrFail($request->store_id);

            foreach ($orderDetailIds as $orderDetailId) {

                $orderDetail = OrderDetail::findOrFail($orderDetailId);

                if(!$orderDetail->currentState()->is_reserved) {
                    return redirect()->route('own-stores.show', ['store' => $store->id])->withErrors(__('Order detail must be in reserve'));
                }
                if ($orderDetail->store_id != $store->id) {
                    return redirect()->route('own-stores.show', ['store' => $store->id])->withErrors(__('Product :reference is on other store', [
                        'reference' => $orderDetail->product->reference
                    ]));
                }

                Operation::create(
                    array_merge(
                        [
                            'type' => 'C',
                            'comment' => 'Перенос товара на склад '.$toStore->name.': '.$request->comment,
                            'user_id' => Auth::id(),
                            'order_id' => $order->id,
                            'order_detail_id' => $orderDetail->id,
                            'is_reservation' => 0,
                            'operable_type' => Product::class,
                            'operable_id' => $orderDetail->product->id,
                            'quantity' => 1,
                            'storage_type' => Store::class,
                            'storage_id' => $store->id,
                            'is_transfer' => 1,
                        ]
                    )
                );

                Operation::create(
                    array_merge(
                        [
                            'type' => 'D',
                            'comment' => 'Перенос товара со склада '.$store->name.': '.$request->comment,
                            'user_id' => Auth::id(),
                            'order_id' => $order->id,
                            'order_detail_id' => $orderDetail->id,
                            'is_reservation' => 0,
                            'operable_type' => Product::class,
                            'operable_id' => $orderDetail->product->id,
                            'quantity' => 1,
                            'storage_type' => Store::class,
                            'storage_id' => $request->store_id,
                            'is_transfer' => 1,
                        ]
                    )
                );

                //Резервируем товар который поступил на новый склад
                Operation::create(
                    array_merge(
                        [
                            'type' => 'C',
                            'comment' => __('Reservation by order'),
                            'user_id' => Auth::id(),
                            'order_id' => $order->id,
                            'order_detail_id' => $orderDetail->id,
                            'is_reservation' => 1,
                            'operable_type' => Product::class,
                            'operable_id' => $orderDetail->product->id,
                            'quantity' => 1,
                            'storage_type' => Store::class,
                            'storage_id' => $request->store_id,
                            'is_transfer' => 0,
                        ]
                    )
                );

                //Создаем комментарий к заказу
                $orderDetail->order->comments()->create([
                    'comment' => __('Product changed')." '{$orderDetail->product->name}': " . __('Reserve'),
                    'user_id' => \Auth::id() ?: null,
                ]);
            }

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            }

            throw $exception;

        }

        DB::commit();

        return redirect()->route('stores.show', compact('store'));
    }

    /**
     * Отображение формы переноса товаров с помощью CSV файла
     *
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function transferCSV(Store $store)
    {
        return view('stores.transfer-csv', compact('store'));
    }

    /**
     * Перенос товаров с помощью CSV файла
     *
     * @param CsvTransferRequest $request
     * @param Store $store
     * @return RedirectResponse
     * @throws \Exception
     */
    public function transferProductsCSV(CsvTransferRequest $request, Store $store)
    {
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        if ($file->getClientOriginalExtension() != 'csv') {
            throw ValidationException::withMessages([__('Need a CSV file.')]);
        }

        /**
         * @var LaravelExcelReader $excel
         */
        $excel = Excel::load(
            $path,
            function ($reader) {
            }
        );

        $data = $excel->get()->toArray();

        if (count($data) < 1) {
            throw ValidationException::withMessages([__('CSV file is Empty.')]);
        }

        collect(['reference', 'quantity'])->each(
            function ($item) use ($data) {
                if (!collect($data[0])->has($item)) {
                    throw ValidationException::withMessages([__('CSV file no need column:').' '.$item]);
                }
            }
        );

        $combinedData = [];
        $errorMessages = collect();
        foreach ($data as $item) {
            $product = Product::where('reference', $item['reference'])->first();
            if ($product) {
                foreach ($product->getSimpleProducts() as $simpleProduct) {
                    $combinedData[$simpleProduct->id] = [
                        'product' => $simpleProduct,
                        'quantity' => isset($combinedData[$simpleProduct->id]['quantity']) ? (int)$combinedData[$simpleProduct->id]['quantity'] + (int)$item['quantity'] : (int)$item['quantity'],
                    ];
                }
            } else {
                $errorMessages->push(
                    __(
                        'Product not found: :product.',
                        [
                            'product' => $item['reference'],
                        ]
                    )
                );
            }
        }
        $data = $combinedData;

        collect($data)->each(
            function ($item) use ($store) {
                if ((int)$item['quantity'] > 0) {
                    /**
                     * @var \App\Product $product
                     */
                    $product = $item['product'];
                    if ((int)$item['quantity'] > $product->getCombinedQuantity($store)) {
                        throw ValidationException::withMessages(
                            [
                                __(
                                    'Product :product max quantity is: :maxQuantity.',
                                    [
                                        'product' => $product->reference,
                                        'maxQuantity' => $product->getCombinedQuantity($store),
                                    ]
                                ),
                            ]
                        );
                    }
                }
            }
        );

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            collect($data)->each(
                function ($item) use ($store, $request) {
                    if ((int)$item['quantity'] > 0) {
                        /**
                         * @var \App\Product $product
                         */
                        $product = $item['product'];
                        $toStore = Store::find($request->store_id);

                        /**
                         * @var User $user
                         */
                        $user = Auth::user();


                        Operation::create(
                            [
                                'type' => 'C',
                                'quantity' => (int)$item['quantity'],
                                'comment' => 'Перенос товара на склад '.$toStore->name,
                                'user_id' => $user->id,
                                'order_id' => 0,
                                'order_detail_id' => 0,
                                'is_reservation' => 0,
                                'operable_type' => Product::class,
                                'operable_id' => $product->id,
                                'storage_type' => Store::class,
                                'storage_id' => $store->id,
                            ]
                        );

                        Operation::create(
                            [
                                'type' => 'D',
                                'quantity' => (int)$item['quantity'],
                                'comment' => 'Перенос товара со склада '.$store->name,
                                'user_id' => $user->id,
                                'order_id' => 0,
                                'order_detail_id' => 0,
                                'is_reservation' => 0,
                                'operable_type' => Product::class,
                                'operable_id' => $product->id,
                                'storage_type' => Store::class,
                                'storage_id' => $toStore->id,
                            ]
                        );


                    }
                }
            );

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            }

            throw $exception;

        }


        DB::commit();


        return back()->with('success', __('Transfer successful'))->withErrors($errorMessages);
    }

    /**
     * Количество товаров на складе
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|int
     */
    public function getProductQuantity(Request $request)
    {
        $quantity = Product::find($request->product_id)->getCombinedQuantity(Store::find($request->store_id));
        $reference = Product::find($request->product_id)->reference;

        return $request->ajax() ? response()->json(['quantity' => $quantity, 'reference' => $reference]) : $quantity;
    }

    /**
     * Количество товаров на складе в CSV файле
     *
     * @param Store $store
     */
    public function getCSV(Store $store)
    {
        $storeProductIds = $store->operableIds();
        $data = Product::all()->map(
            function (Product $product) use (
                $store,
                $storeProductIds
            ) {
                return collect(
                    [
                        'manufacturer' => $product->manufacturer->name,
                        'name' => $product->name,
                        'reference' => $product->reference,
                        'quantity' => $storeProductIds->search($product->id) ? $store->getRealCurrentQuantity(
                            $product->id
                        ) ?: '0' : '0',
                    ]
                );
            }
        );

        if ($data->count() == 0) {
            $data->push(
                collect(
                    [
                        'manufacturer' => '',
                        'name' => '',
                        'reference' => '',
                        'quantity' => '',
                    ]
                )
            );
        }

        $data = $data->toArray();

        /**
         * @var LaravelExcelWriter $excel
         */
        $excel = Excel::create(
            'store_'.$store->id.'_products_'.Carbon::now()->format('d_m_Y_H_i'),
            function (LaravelExcelWriter $excel) use ($data) {
                $excel->sheet(
                    'Sheet',
                    function (LaravelExcelWorksheet $sheet) use ($data) {
                        $sheet->fromArray($data);
                    }
                );
            }
        );

        $excel->download('csv');
    }

    /**
     * Количество свободных товаров на складе в CSV файле
     *
     * @param Store $store
     */
    public function getCurrentCSV(Store $store)
    {
        $storeProductIds = $store->operableIds();
        $data = Product::all()->map(
            function (Product $product) use (
                $store,
                $storeProductIds
            ) {
                return collect(
                    [
                        'manufacturer' => $product->manufacturer->name,
                        'name' => $product->name,
                        'reference' => $product->reference,
                        'quantity' => $storeProductIds->search($product->id) ? $store->getCurrentQuantity(
                            $product->id
                        ) ?: '0' : '0',
                    ]
                );
            }
        );

        if ($data->count() == 0) {
            $data->push(
                collect(
                    [
                        'manufacturer' => '',
                        'name' => '',
                        'reference' => '',
                        'quantity' => '',
                    ]
                )
            );
        }

        $data = $data->toArray();

        /**
         * @var LaravelExcelWriter $excel
         */
        $excel = Excel::create(
            'store_'.$store->id.'_products_'.Carbon::now()->format('d_m_Y_H_i'),
            function (LaravelExcelWriter $excel) use ($data) {
                $excel->sheet(
                    'Sheet',
                    function (LaravelExcelWorksheet $sheet) use ($data) {
                        $sheet->fromArray($data);
                    }
                );
            }
        );

        $excel->download('csv');
    }

    /**
     * Справочник товаров в CSV файле
     */
    public function getCSVReferenceDictionary()
    {
        /**
         * @var LaravelExcelWriter $excel
         */
        $excel = Excel::create(
            'reference_dictionary',
            function (LaravelExcelWriter $excel) {
                $excel->sheet(
                    'Sheet',
                    function (LaravelExcelWorksheet $sheet) {
                        $sheet->fromArray(Product::all('name', 'reference')->toArray());
                    }
                );
            }
        );

        $excel->download('csv');
    }

    /**
     * Оприходование/Инвентаризация товаров из CSV файла
     *
     * @param CsvImportRequest $request
     * @param Store $store
     * @return RedirectResponse
     */
    public function importCSV(CsvImportRequest $request, Store $store)
    {
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        if ($file->getClientOriginalExtension() != 'csv') {
            throw ValidationException::withMessages([__('Need a CSV file.')]);
        }

        /**
         * @var LaravelExcelReader $excel
         */
        $excel = Excel::load(
            $path,
            function ($reader) {
            }
        );

        $data = $excel->get()->toArray();

        if (count($data) < 1) {
            throw ValidationException::withMessages([__('CSV file is Empty.')]);
        }

        collect(['reference', 'quantity'])->each(
            function ($item) use ($data) {
                if (!collect($data[0])->has($item)) {
                    throw ValidationException::withMessages([__('CSV file no need column:').' '.$item]);
                }
            }
        );

        $combinedData = [];
        $errorMessages = collect();
        foreach ($data as $item) {
            $product = Product::where('reference', (string)$item['reference'])->first();
            if ($product instanceof Product && $product->exists()) {
                foreach ($product->getSimpleProducts() as $simpleProduct) {
                    $combinedData[$simpleProduct->id] = [
                        'product' => $simpleProduct,
                        'quantity' => isset($combinedData[$simpleProduct->id]['quantity']) ? (int)$combinedData[$simpleProduct->id]['quantity'] + (int)$item['quantity'] : (int)$item['quantity'],
                    ];
                }
            } else {
                $errorMessages->push(
                    __(
                        'Product not found: :product.',
                        [
                            'product' => $item['reference'],
                        ]
                    )
                );
            }
        }
        $data = $combinedData;

        $user = Auth::user();

        $comment = '';

        if(!empty($request->comment)){
            $comment = ' '. $request->comment;
        }

        switch ($request->operation_type) {
            case 'D':
                collect($data)->each(
                    function ($item) use ($store, $user, $comment) {
                        if ((int)$item['quantity'] > 0) {
                            /**
                             * @var \App\Product $product
                             */
                            $product = $item['product'];

                            Operation::create(
                                [
                                    'type' => 'D',
                                    'quantity' => (int)$item['quantity'],
                                    'comment' => __('Income by CSV Import') . $comment,
                                    'user_id' => $user->id,
                                    'order_id' => 0,
                                    'order_detail_id' => 0,
                                    'is_reservation' => 0,
                                    'operable_type' => Product::class,
                                    'operable_id' => $product->id,
                                    'storage_type' => Store::class,
                                    'storage_id' => $store->id,
                                ]
                            );
                        }
                    }
                );
                break;
            case 'I':
                $storeProducts = Product::whereIn('id', $store->operableIds())->get()->keyBy('id');
                $storeProductsInInventory = collect();
                $cancelReservations = collect();
                collect($data)->each(
                    function ($item) use (
                        $store,
                        $storeProducts,
                        $storeProductsInInventory,
                        $cancelReservations,
                        $user,
                        $comment
                    ) {
                        if ((int)$item['quantity'] >= 0) {
                            /**
                             * @var \App\Product $product
                             */
                            $product = $item['product'];
                            if ($storeProducts->has($product->id)) {
                                $storeProductsInInventory->put($product->id, $product);
                                $realQuantity = $product->getRealCombinedQuantity($store);
                                if ((int)$item['quantity'] > $realQuantity) {

                                    Operation::create(
                                        [
                                            'type' => 'D',
                                            'quantity' => (int)$item['quantity'] - $realQuantity,
                                            'comment' => __('Income by CSV Import').' - '.__('Inventory') . $comment,
                                            'user_id' => $user->id,
                                            'order_id' => 0,
                                            'order_detail_id' => 0,
                                            'is_reservation' => 0,
                                            'operable_type' => Product::class,
                                            'operable_id' => $product->id,
                                            'storage_type' => Store::class,
                                            'storage_id' => $store->id,
                                        ]
                                    );

                                } elseif ((int)$item['quantity'] < $realQuantity) {
                                    $freeQuantity = $product->getCombinedQuantity($store);
                                    $creditQuantity = $realQuantity - (int)$item['quantity'];
                                    if ($freeQuantity < $creditQuantity) {
                                        $needQuantityReservationToCancel = $creditQuantity - $freeQuantity;
                                        $lastReservations = $store->operations()->where(
                                            'operable_id',
                                            $product->id
                                        )->where('is_reservation', 1)->latest()->get()->groupBy('order_detail_id');
                                        $lastReservations->each(
                                            function (Collection $orderDetailReservations) use (
                                                $store,
                                                $product,
                                                $cancelReservations,
                                                $needQuantityReservationToCancel,
                                                $user,
                                                $comment
                                            ) {
                                                $reservationQuantity = $orderDetailReservations->where(
                                                        'type',
                                                        'C'
                                                    )->sum('quantity') - $orderDetailReservations->where(
                                                        'type',
                                                        'D'
                                                    )->sum('quantity');
                                                if ($reservationQuantity > 0) {

                                                    $operation = Operation::create(
                                                        [
                                                            'type' => 'D',
                                                            'quantity' => $reservationQuantity,
                                                            'comment' => __(
                                                                    'Cancel reservation by CSV Import'
                                                                ).' - '.__('Inventory') . $comment,
                                                            'user_id' => $user->id,
                                                            'order_id' => $orderDetailReservations->first()->order_id,
                                                            'order_detail_id' => $orderDetailReservations->first(
                                                            )->order_detail_id,
                                                            'is_reservation' => 1,
                                                            'operable_type' => Product::class,
                                                            'operable_id' => $product->id,
                                                            'storage_type' => Store::class,
                                                            'storage_id' => $store->id,
                                                        ]
                                                    );

                                                    $cancelReservations->push($operation);
                                                    $needQuantityReservationToCancel = $needQuantityReservationToCancel - $reservationQuantity;
                                                    if ($needQuantityReservationToCancel <= 0) {
                                                        return false;
                                                    }
                                                }

                                                return true;
                                            }
                                        );
                                    }

                                    Operation::create(
                                        [
                                            'type' => 'C',
                                            'quantity' => $creditQuantity,
                                            'comment' => __('Write-off by CSV Import').' - '.__('Inventory') . $comment,
                                            'user_id' => $user->id,
                                            'order_id' => 0,
                                            'order_detail_id' => 0,
                                            'is_reservation' => 0,
                                            'operable_type' => Product::class,
                                            'operable_id' => $product->id,
                                            'storage_type' => Store::class,
                                            'storage_id' => $store->id,
                                        ]
                                    );

                                }
                            } elseif ((int)$item['quantity'] > 0) {

                                Operation::create(
                                    [
                                        'type' => 'D',
                                        'quantity' => (int)$item['quantity'],
                                        'comment' => __('Income by CSV Import').' - '.__('Inventory'),
                                        'user_id' => $user->id,
                                        'order_id' => 0,
                                        'order_detail_id' => 0,
                                        'is_reservation' => 0,
                                        'operable_type' => Product::class,
                                        'operable_id' => $product->id,
                                        'storage_type' => Store::class,
                                        'storage_id' => $store->id,
                                    ]
                                );

                            };
                        }
                    }
                );
                $storeProducts->whereNotIn('id', $storeProductsInInventory->keys())->each(
                    function (Product $product) use ($store, $storeProductsInInventory, $cancelReservations, $user) {
                        $realQuantity = $store->getRealCurrentQuantity($product->id);
                        if ($realQuantity > 0) {
                            $freeQuantity = $store->getCurrentQuantity($product->id);
                            $creditQuantity = $realQuantity;
                            if ($freeQuantity < $creditQuantity) {
                                $needQuantityReservationToCancel = $creditQuantity - $freeQuantity;
                                $lastReservations = $store->operations()->where('operable_id', $product->id)->where(
                                    'is_reservation',
                                    1
                                )->latest()->get()->groupBy('order_detail_id');
                                $lastReservations->each(
                                    function (Collection $orderDetailReservations) use (
                                        $store,
                                        $product,
                                        $cancelReservations,
                                        $needQuantityReservationToCancel,
                                        $user
                                    ) {
                                        $reservationQuantity = $orderDetailReservations->where('type', 'C')->sum(
                                                'quantity'
                                            ) - $orderDetailReservations->where('type', 'D')->sum('quantity');
                                        if ($reservationQuantity > 0) {

                                            $operation = Operation::create(
                                                [
                                                    'type' => 'D',
                                                    'quantity' => $reservationQuantity,
                                                    'comment' => __(
                                                            'Cancel reservation by CSV Import'
                                                        ).' - '.__('Inventory'),
                                                    'user_id' => $user->id,
                                                    'order_id' => $orderDetailReservations->first()->order_id,
                                                    'order_detail_id' => $orderDetailReservations->first(
                                                    )->order_detail_id,
                                                    'is_reservation' => 1,
                                                    'operable_type' => Product::class,
                                                    'operable_id' => $product->id,
                                                    'storage_type' => Store::class,
                                                    'storage_id' => $store->id,
                                                ]
                                            );

                                            $cancelReservations->push($operation);
                                            $needQuantityReservationToCancel = $needQuantityReservationToCancel - $reservationQuantity;
                                            if ($needQuantityReservationToCancel <= 0) {
                                                return false;
                                            }
                                        }

                                        return true;
                                    }
                                );
                            }

                            Operation::create(
                                [
                                    'type' => 'C',
                                    'quantity' => $creditQuantity,
                                    'comment' => __('Write-off by CSV Import').' - '.__('Inventory'),
                                    'user_id' => $user->id,
                                    'order_id' => 0,
                                    'order_detail_id' => 0,
                                    'is_reservation' => 0,
                                    'operable_type' => Product::class,
                                    'operable_id' => $product->id,
                                    'storage_type' => Store::class,
                                    'storage_id' => $store->id,
                                ]
                            );

                        }
                    }
                );
                $cancelReservations->groupBy('order_id')->each(
                    function (
                        Collection $orderCancelReservations,
                        $orderId
                    ) use ($errorMessages) {
                        $order = Order::find($orderId);
                        $order->orderDetails->each(
                            function (OrderDetail $orderDetail) {
                                $orderDetail->states()->get()->each(
                                    function (OrderDetailState $orderDetailState) use (
                                        $orderDetail
                                    ) {
                                        if ($orderDetailState->is_block_editing_order_detail == 'enable' && $orderDetailState->store_operation == 'DR') {
                                            $orderDetail->states()->save($orderDetailState);

                                            return false;
                                        }

                                        return true;
                                    }
                                );
                            }
                        );
                        $errorMessages->push(
                            __(
                                'Reserve for order :order was canceled. Products: :products.',
                                [
                                    'order' => $order->id,
                                    'products' => implode(
                                        ', ',
                                        $orderCancelReservations->map(
                                            function (Operation $operation) {
                                                return $operation->operable->name;
                                            }
                                        )->toArray()
                                    ),
                                ]
                            )
                        );
                    }
                );
                break;
            case 'ICHUNK':
                $storeProducts = Product::whereIn('id', $store->operableIds())->get()->keyBy('id');
                $storeProductsInInventory = collect();
                $cancelReservations = collect();
                collect($data)->each(
                    function ($item) use (
                        $store,
                        $storeProducts,
                        $storeProductsInInventory,
                        $cancelReservations,
                        $user
                    ) {
                        if ((int)$item['quantity'] >= 0) {
                            /**
                             * @var \App\Product $product
                             */
                            $product = $item['product'];
                            if ($storeProducts->has($product->id)) {
                                $storeProductsInInventory->put($product->id, $product);
                                $realQuantity = $product->getRealCombinedQuantity($store);
                                if ((int)$item['quantity'] > $realQuantity) {

                                    Operation::create(
                                        [
                                            'type' => 'D',
                                            'quantity' => (int)$item['quantity'] - $realQuantity,
                                            'comment' => __('Income by CSV Import').' - '.__('Inventory'),
                                            'user_id' => $user->id,
                                            'order_id' => 0,
                                            'order_detail_id' => 0,
                                            'is_reservation' => 0,
                                            'operable_type' => Product::class,
                                            'operable_id' => $product->id,
                                            'storage_type' => Store::class,
                                            'storage_id' => $store->id,
                                        ]
                                    );

                                } elseif ((int)$item['quantity'] < $realQuantity) {
                                    $freeQuantity = $product->getCombinedQuantity($store);
                                    $creditQuantity = $realQuantity - (int)$item['quantity'];
                                    if ($freeQuantity < $creditQuantity) {
                                        $needQuantityReservationToCancel = $creditQuantity - $freeQuantity;
                                        $lastReservations = $store->operations()->where(
                                            'operable_id',
                                            $product->id
                                        )->where('is_reservation', 1)->latest()->get()->groupBy('order_detail_id');
                                        $lastReservations->each(
                                            function (Collection $orderDetailReservations) use (
                                                $store,
                                                $product,
                                                $cancelReservations,
                                                $needQuantityReservationToCancel,
                                                $user
                                            ) {
                                                $reservationQuantity = $orderDetailReservations->where(
                                                        'type',
                                                        'C'
                                                    )->sum('quantity') - $orderDetailReservations->where(
                                                        'type',
                                                        'D'
                                                    )->sum('quantity');
                                                if ($reservationQuantity > 0) {

                                                    $operation = Operation::create(
                                                        [
                                                            'type' => 'D',
                                                            'quantity' => $reservationQuantity,
                                                            'comment' => __(
                                                                    'Cancel reservation by CSV Import'
                                                                ).' - '.__('Inventory'),
                                                            'user_id' => $user->id,
                                                            'order_id' => $orderDetailReservations->first()->order_id,
                                                            'order_detail_id' => $orderDetailReservations->first(
                                                            )->order_detail_id,
                                                            'is_reservation' => 1,
                                                            'operable_type' => Product::class,
                                                            'operable_id' => $product->id,
                                                            'storage_type' => Store::class,
                                                            'storage_id' => $store->id,
                                                        ]
                                                    );

                                                    $cancelReservations->push($operation);
                                                    $needQuantityReservationToCancel = $needQuantityReservationToCancel - $reservationQuantity;
                                                    if ($needQuantityReservationToCancel <= 0) {
                                                        return false;
                                                    }
                                                }

                                                return true;
                                            }
                                        );
                                    }

                                    Operation::create(
                                        [
                                            'type' => 'C',
                                            'quantity' => $creditQuantity,
                                            'comment' => __('Write-off by CSV Import').' - '.__('Inventory'),
                                            'user_id' => $user->id,
                                            'order_id' => 0,
                                            'order_detail_id' => 0,
                                            'is_reservation' => 0,
                                            'operable_type' => Product::class,
                                            'operable_id' => $product->id,
                                            'storage_type' => Store::class,
                                            'storage_id' => $store->id,
                                        ]
                                    );

                                }
                            } elseif ((int)$item['quantity'] > 0) {

                                Operation::create(
                                    [
                                        'type' => 'D',
                                        'quantity' => (int)$item['quantity'],
                                        'comment' => __('Income by CSV Import').' - '.__('Inventory'),
                                        'user_id' => $user->id,
                                        'order_id' => 0,
                                        'order_detail_id' => 0,
                                        'is_reservation' => 0,
                                        'operable_type' => Product::class,
                                        'operable_id' => $product->id,
                                        'storage_type' => Store::class,
                                        'storage_id' => $store->id,
                                    ]
                                );

                            };
                        }
                    }
                );
                $cancelReservations->groupBy('order_id')->each(
                    function (
                        Collection $orderCancelReservations,
                        $orderId
                    ) use ($errorMessages) {
                        $order = Order::find($orderId);
                        $order->orderDetails->each(
                            function (OrderDetail $orderDetail) {
                                $orderDetail->states()->get()->each(
                                    function (OrderDetailState $orderDetailState) use (
                                        $orderDetail
                                    ) {
                                        if ($orderDetailState->is_block_editing_order_detail == 'enable' && $orderDetailState->store_operation == 'DR') {
                                            $orderDetail->states()->save($orderDetailState);

                                            return false;
                                        }

                                        return true;
                                    }
                                );
                            }
                        );
                        $errorMessages->push(
                            __(
                                'Reserve for order :order was canceled. Products: :products.',
                                [
                                    'order' => $order->id,
                                    'products' => implode(
                                        ', ',
                                        $orderCancelReservations->map(
                                            function (Operation $operation) {
                                                return $operation->operable->name;
                                            }
                                        )->toArray()
                                    ),
                                ]
                            )
                        );
                    }
                );
                break;
        }

        return back()->with('success', __('Import successful'))->withErrors($errorMessages);
    }

    /**
     * Постраничка для коллекции
     *
     * TODO Стоит перенести этот метод как универсальный для коллекций
     * @param Collection $items
     * @param int $perPage
     * @param null $page
     * @param array $options
     * @return LengthAwarePaginator
     */
    protected function paginate(Collection $items, $perPage = 15, $page = null, array $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        if(!in_array('path', $options)){
            $options['path'] = Paginator::resolveCurrentPath();
        }
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function findReserve(Store $store, Request $request)
    {
        $doingErrors = [];
        $orderDetails = [];

        if (!in_array(\Auth::id(), $store->users->pluck('id')->toArray())) {
            $doingErrors[] = __(
                'You have no access to :store',
                [
                    'store' => $store->name,
                ]
            );

            return redirect()->route('stores.show', ['store' => $store->id])->withErrors($doingErrors);
        }
        if (empty($request->reference)) {
            return view('stores.find-reserve', compact('orderDetails'));
        }
        $product = Product::where('reference', $request->reference)->first();

        if (!$product) {
            $doingErrors[] = __('Product not found');
            return back()->withErrors($doingErrors);
        }

        $orderDetails = OrderDetail::where('product_id', $product->id)->get();
        $orderDetails = $orderDetails->filter(function(OrderDetail $orderDetail) use ($store){
            $credit = Operation::query()
                ->where('is_reservation', 1)
                ->where('operable_type', Product::class)
                ->where('storage_id', $store->id)
                ->where('order_detail_id', $orderDetail->id)
                ->where('type', 'C')
                ->sum('quantity');

            $debit = Operation::query()
                ->where('is_reservation', 1)
                ->where('operable_type', Product::class)
                ->where('storage_id', $store->id)
                ->where('order_detail_id', $orderDetail->id)
                ->where('type', 'D')
                ->sum('quantity');
            if($debit - $credit < 0){
                return true;
            }
        });

        return view('stores.find-reserve', compact('orderDetails'));
    }

     /**
     * Скрывать склад в меню
     *
     * @param Store $store
     * @return RedirectResponse
     */
    public function hideInMenu(Store $store)
    {
        $store->is_hidden  = 1;
        $store->save();
        return redirect()
            ->route('stores.index');
    }

    /**
     * Показывать склад в меню
     *
     * @param Store $store
     * @return RedirectResponse
     */
    public function showInMenu(Store $store)
    {
        $store->is_hidden  = 0;
        $store->save();
        return redirect()
            ->route('stores.index');
    }
}
