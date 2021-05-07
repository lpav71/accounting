<?php

namespace App\Http\Controllers;

use App\Exceptions\DoingException;
use App\Operation;
use App\Product;
use App\Store;
use App\TransferIteration;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransferIterationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:transfer-iterations-list');
        $this->middleware('permission:transfer-iteration-create', ['only' => ['store']]);
        $this->middleware('permission:transfer-iterations-process', ['only' => ['show','process']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        return view('transfer-iterations.index', ['iterations' => TransferIteration::orderBy('id', 'ASC')->paginate(15)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        TransferIteration::create([
            'store_id_from' => (int)$request->input('store_id_from'),
            'store_id_to' => (int)$request->input('store_id_to'),
            'settings' => serialize($request->input('transfer'))
        ]);

        return redirect(route('transfer-iterations.index'));

    }

    /**
     * Display the specified resource.
     *
     * @param \App\TransferIteration $transferIteration
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function show(TransferIteration $transferIteration)
    {
        $storeFrom = $transferIteration->storeFrom;
        $storeTo = $transferIteration->storeTo;
        $productIds = $transferIteration->productIds();
        $products = Product::find($productIds);
        $transferProducts = [];
        foreach ($products as $product) {
            $transferProducts[$product->id]['reference'] = $product->reference;
            $transferProducts[$product->id]['current'] = $product->getCombinedQuantity($storeFrom);
            $transferProducts[$product->id]['transfer'] = $transferIteration->getSettings()[$product->id];
            $transferProducts[$product->id]['currentReserve'] = $product->getCombinedQuantity($storeTo);
        }
        return view('transfer-iterations.show', compact('transferIteration', 'transferProducts'));
    }


    public function process(Request $request)
    {
        $this->validate(
            $request,
            [
                'store_id_to' => 'integer|min:0',
                'store_id_from' => 'integer|min:0',
                'product' => 'array',
                'transfer_iteration_id' => 'integer|min:0'
            ]
        );
        $iteration = TransferIteration::find($request->input('transfer_iteration_id'));
        if ($iteration->is_completed) {
            ValidationException::withMessages(['Iteration completed']);
        }
        $products = [];
        foreach ($request->input('product') as $key => $product) {
            for ($i = 1; $i <= $product; $i++) {
                $products[] = $key;
            }
        }


        $user = Auth::user();
        $storeFrom = Store::find($request->input('store_id_from'));
        $storeTo = Store::find($request->input('store_id_to'));
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            foreach ($products as $productId) {

                $productId = preg_replace('/[0-9]+\-/', '', $productId);

                Operation::create(
                    array_merge(
                        [
                            'type' => 'C',
                            'comment' => 'Итерация переносов товара на склад ' . $storeTo->name,
                            'user_id' => $user->id,
                            'order_id' => 0,
                            'order_detail_id' => 0,
                            'is_reservation' => 0,
                            'operable_type' => Product::class,
                            'operable_id' => $productId,
                            'quantity' => 1,
                            'storage_type' => Store::class,
                            'storage_id' => $storeFrom->id,
                            'is_transfer' => 1,
                        ]
                    )
                );

                Operation::create(
                    array_merge(
                        [
                            'type' => 'D',
                            'comment' => 'Перенос товара со склада ' . $storeFrom->name,
                            'user_id' => $user->id,
                            'order_id' => 0,
                            'order_detail_id' => 0,
                            'is_reservation' => 0,
                            'operable_type' => Product::class,
                            'operable_id' => $productId,
                            'quantity' => 1,
                            'storage_type' => Store::class,
                            'storage_id' => $storeTo->id,
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

        $iteration->update([
            'is_completed' => 1,
            'transfered_count' => count($products)
        ]);
        return redirect(route('transfer-iterations.index'));
    }

}
