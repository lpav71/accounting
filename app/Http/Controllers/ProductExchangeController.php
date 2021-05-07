<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\Certificate;
use App\Currency;
use App\Exceptions\DoingException;
use App\Http\Requests\ProductExchangeRequest;
use App\Order;
use App\OrderDetail;
use App\OrderDetailState;
use App\Product;
use App\ProductExchange;
use App\ProductExchangeState;
use App\Role;
use App\Services\ThermalPrinter\ThermalPrinter;
use App\Store;
use App\User;
use DB;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use App;

class ProductExchangeController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:order-list');
        $this->middleware('permission:order-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:order-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список обменов
     *
     * @return Response
     */
    public function index()
    {
        $productExchanges = ProductExchange::orderBy('id', 'desc')->paginate(50);

        return view('product-exchanges.index', compact('productExchanges'));
    }

    /**
     * Отображение формы для создания нового обмена
     *
     * @param Order $order
     * @return Response
     */
    public function create(Order $order)
    {

        $states = ProductExchangeState::all()->filter(
            function (ProductExchangeState $productExchangeState) {
                return $productExchangeState->previousStates->isEmpty();
            }
        )->pluck('name', 'id');

        $stores = Store::all()->pluck('name', 'id');
        $products = Product::all()->pluck('name', 'id');
        $currencies = Currency::all()->pluck('name', 'id');

        $orderDetailStartStates = OrderDetailState::where('is_hidden', 0)
            ->where('owner_type', ProductExchange::class)
            ->get()
            ->filter(
                function (
                    OrderDetailState $orderDetailState
                ) {
                    return !$orderDetailState->previousStates()->count();
                }
            )->pluck('name', 'id');

        $carriers = Carrier::all()->pluck('name', 'id')->prepend(__('Unknown'), '0');

        $availableOrderDetails = $order
            ->orderDetails()
            ->where('is_exchange', 0)
            ->get()
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
            'product-exchanges.create',
            compact(
                'order',
                'states',
                'carriers',
                'availableOrderDetails',
                'stores',
                'products',
                'currencies',
                'orderDetailStartStates'
            )
        );
    }

    /**
     * Сохранение данных из формы создания нового обмена
     *
     * @param  ProductExchangeRequest $request
     * @return Response
     * @throws \Exception
     */
    public function store(ProductExchangeRequest $request)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $productExchange = ProductExchange::create($request->input());

            $orderDetails = collect($request->order_detail);

            $orderDetails->each(
                function ($orderDetailArray, $orderDetailId) use ($productExchange) {

                    $orderDetail = OrderDetail::find($orderDetailId);

                    $orderDetail->update(
                        array_merge(
                            $orderDetailArray,
                            [
                                'product_exchange_id' => $productExchange->id,
                            ]
                        )
                    );

                    $orderDetail->states()->save(OrderDetailState::find($orderDetailArray['order_detail_state_id']));
                }
            );

            $orderDetailsAdd = collect($request->order_detail_add);

            $orderDetailsAdd->each(
                function ($orderDetailArray) use ($productExchange) {

                    $orderDetail = OrderDetail::create(
                        array_merge(
                            $orderDetailArray,
                            [
                                'order_id' => $productExchange->order->id,
                                'product_exchange_id' => $productExchange->id,
                                'owner_type' => ProductExchange::class,
                                'is_exchange' => 1,
                            ]
                        )
                    );

                    $orderDetail->states()->save(OrderDetailState::find($orderDetailArray['order_detail_state_id']));

                    if($orderDetail->product->category->is_certificate) {
                        Certificate::create([
                            'number' => $orderDetailArray['certificate_number'],
                            'order_detail_id' => $orderDetail->id
                        ]);
                    }
                }
            );

            $productExchange->states()->save(ProductExchangeState::find($request->product_exchange_state_id));


        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }


        DB::commit();

        return redirect()
            ->route('product-exchanges.index')
            ->with('success', __('Product exchange created successfully'));
    }

    /**
     * Отображение формы редактирования обмена
     *
     * @param  ProductExchange $productExchange
     * @return Response
     */
    public function edit(ProductExchange $productExchange)
    {
        $states = ProductExchangeState::all()
            ->filter(
                function (ProductExchangeState $productExchangeState) use ($productExchange) {
                    return $productExchangeState
                        ->previousStates()
                        ->where('product_exchange_states.id', $productExchange->currentState()->id)
                        ->count();
                }
            )
            ->pluck('name', 'id')
            ->prepend($productExchange->currentState()->name, $productExchange->currentState()->id);

        $stores = Store::all()->pluck('name', 'id');
        $products = Product::all()->pluck('name', 'id');
        $currencies = Currency::all()->pluck('name', 'id');

        $orderDetailStartStates = OrderDetailState::where('is_hidden', 0)
            ->where('owner_type', ProductExchange::class)
            ->get()
            ->filter(
                function (
                    OrderDetailState $orderDetailState
                ) {
                    return !$orderDetailState->previousStates()->count();
                }
            )->pluck('name', 'id');

        $carriers = Carrier::all()->pluck('name', 'id')->prepend(__('Unknown'), '0');

        $orderDetails = $productExchange->orderDetails;
        $exchangeOrderDetails = $productExchange->exchangeOrderDetails;

        $order = $productExchange->order;

        return view(
            'product-exchanges.edit',
            compact(
                'productExchange',
                'states',
                'stores',
                'products',
                'currencies',
                'orderDetailStartStates',
                'carriers',
                'orderDetails',
                'exchangeOrderDetails',
                'order'
            )
        );
    }

    /**
     * Сохранение данных из формы редактирования обмена
     *
     * @param  ProductExchangeRequest $request
     * @param  ProductExchange $productExchange
     * @return Response
     * @throws \Exception
     */
    public function update(ProductExchangeRequest $request, ProductExchange $productExchange)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $productExchange->update($request->input());

            $orderDetails = collect($request->order_detail);

            $orderDetails->each(
                function ($orderDetailArray, $orderDetailId) use ($productExchange) {

                    $orderDetail = OrderDetail::find($orderDetailId);

                    $orderDetail->update(
                        array_merge(
                            $orderDetailArray,
                            [
                                'product_exchange_id' => $productExchange->id,
                            ]
                        )
                    );

                    $orderDetail->states()->save(OrderDetailState::find($orderDetailArray['order_detail_state_id']));
                }
            );

            $exchangeOrderDetails = collect($request->exchange_order_detail);
            $orderDetailsNew = collect($request->order_detail_add);


            //Удаление товарных позиций, присутствующих в обмене и отсутствующих в запросе
            $productExchange->exchangeOrderDetails()
                ->whereNotIn('id', $exchangeOrderDetails->keys())
                ->get()
                ->each(
                    function (OrderDetail $orderDetail) {
                        if($orderDetail->product->category->is_certificate) {
                            $orderDetail->certificate->delete();
                        }
                        $orderDetail->delete(); // Без обхода не перехватывается событие "удалить" в Observer
                    }
                );

            //Добавление новых товарных позиций из запроса
            $orderDetailsNew->each(
                function ($orderDetailArray) use ($productExchange) {

                    $orderDetail = OrderDetail::create(
                        array_merge(
                            $orderDetailArray,
                            [
                                'order_id' => $productExchange->order->id,
                                'product_exchange_id' => $productExchange->id,
                                'owner_type' => ProductExchange::class,
                                'is_exchange' => 1,
                            ]
                        )
                    );

                    $orderDetail->states()->save(OrderDetailState::find($orderDetailArray['order_detail_state_id']));

                    if($orderDetail->product->category->is_certificate) {
                        Certificate::create([
                            'number' => $orderDetailArray['certificate_number'],
                            'order_detail_id' => $orderDetail->id
                        ]);
                    }

                }
            );

            // Обновление товарных позиций
            $exchangeOrderDetails->each(
                function ($orderDetailData, $orderDetailId) use ($productExchange) {

                    $orderDetail = OrderDetail::find($orderDetailId);
                    $orderDetail->update($orderDetailData);
                    $orderDetail->refresh();

                    if ($orderDetail->product->category->is_certificate && $orderDetailData['certificate_number'] != 0) {
                        if(Certificate::where(['order_detail_id' => $orderDetail->id])->first()) {
                            $orderDetail->certificate()->update([
                                'number' => $orderDetailData['certificate_number'] ? $orderDetailData['certificate_number'] : null,
                            ]);
                        } else {
                            Certificate::create([
                                'number' => $orderDetailData['certificate_number'] ? $orderDetailData['certificate_number'] : null,
                                'order_detail_id' => $orderDetail->id
                            ]);
                        }
                    }

                    if (isset($orderDetailData['order_detail_state_id'])) {
                        $orderDetail->states()->save(OrderDetailState::find($orderDetailData['order_detail_state_id']));
                    }
                }
            );

            $productExchange->states()->save(ProductExchangeState::find($request->product_exchange_state_id));


        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }

        DB::commit();

        return redirect()
            ->route('product-exchanges.edit', $productExchange)
            ->with('success', __('Product exchange updated successfully'));
    }

    /**
     * Получение печатных форм для обмена
     *
     * @param \App\ProductExchange $productExchange
     * @return \Illuminate\Http\Response
     */
    public function getPDF(ProductExchange $productExchange)
    {
        ThermalPrinter::printExchange($productExchange);
        $ExchangeOrderDetails=$productExchange->exchangeOrderDetails()->get();
        $order=$productExchange->order()->first();
        $template = '<style> .page-break { page-break-after: always; } </style>';
        $replacements = [
            'src="/' => 'src="'.public_path('/'),
            '{Order.number}' => $order->getDisplayNumber(),
            '{Order.date}' => $productExchange->created_at->format('d.m.Y'),
            '{Order.delivery_city}' => $productExchange->delivery_city,
            '{Order.delivery_address}' => $productExchange->getStreetDeliveryAddress(),
            '{Order.date_estimated_delivery}' => is_null(
                $productExchange->delivery_estimated_date
            ) ? '' : Carbon::createFromFormat('d-m-Y', $productExchange->delivery_estimated_date)->format('d.m.Y'),
            '{Order.delivery_start_time}' => $productExchange->delivery_start_time,
            '{Order.delivery_end_time}' => $productExchange->delivery_end_time,
            '{Customer.phone}' => $order->customer->phone,
            '{Customer.name}' => $order->customer->first_name.' '.$order->customer->last_name,
        ];
        $firstPage = true;

        /**
         * @var Collection $orderDetails
         */
        foreach ($ExchangeOrderDetails->groupBy('printing_group') as $printingGroup => $orderDetails) {
            if (!$firstPage) {
                $template .= '<div class="page-break"></div>';
            } else {
                $firstPage = false;
            }

            if ($printingGroup) {
                $replacements['{Order.number}'] .= '.'.$printingGroup;
            }

            $replacements['{Order.Invoice.Sum}'] = sprintf("%01.0f", $orderDetails->sum('price'));

            $replacements['<tr><td>{Order.Invoice.Products}</td></tr>'] = '';
            $replacements['<tr><td>{Order.Cheque.Products}</td></tr>'] = '';

            /**
             * @var OrderDetail $orderDetail
             */
            $rowNumber = 1;
            foreach ($orderDetails as $orderDetail) {
                $replacements['<tr><td>{Order.Invoice.Products}</td></tr>'] .= '<tr><td>'.$orderDetail->product->name.'</td><td>'.sprintf(
                        "%01.0f",
                        $orderDetail->price
                    ).'</td><td>1</td><td>'.sprintf("%01.0f", $orderDetail->price).'</td></tr>';
                $replacements['<tr><td>{Order.Cheque.Products}</td></tr>'] .= '<tr><td>'.$rowNumber.'</td><td>'.$orderDetail->product->name.'</td><td>шт</td><td>1</td><td>'.sprintf(
                        "%01.0f",
                        $orderDetail->price
                    ).'</td><td>'.sprintf("%01.0f", $orderDetail->price).'</td></tr>';
                $rowNumber++;
            }

            $currentInvoiceTemplate = $order->channel->invoice_template;
            $currentChequeTemplate = $order->channel->cheque_template;

            foreach ($replacements as $search => $replace) {
                $currentInvoiceTemplate = str_replace($search, $replace, $currentInvoiceTemplate);
                $currentChequeTemplate = str_replace($search, $replace, $currentChequeTemplate);
                foreach ($orderDetails as $orderDetail) {
                    $replacements['<tr><td>{Order.Invoice.Products}</td></tr>'] .= '<tr><td>'.$orderDetail->product->name.'</td><td>'.sprintf(
                            "%01.0f",
                            $orderDetail->price
                        ).'</td><td>1</td><td>'.sprintf("%01.0f", $orderDetail->price).'</td></tr>';
                    $replacements['<tr><td>{Order.Cheque.Products}</td></tr>'] .= '<tr><td>'.$rowNumber.'</td><td>'.$orderDetail->product->name.'</td><td>шт</td><td>1</td><td>'.sprintf(
                            "%01.0f",
                            $orderDetail->price
                        ).'</td><td>'.sprintf("%01.0f", $orderDetail->price).'</td></tr>';
                    $rowNumber++;
                }
            }

            $allGuarantee = '';
            $productNumber = 1;
            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($orderDetails as $orderDetail) {
                if (!$orderDetail->product->need_guarantee) {
                    continue;
                }
                $currentGuaranteeTemplate = $order->channel->guarantee_template;
                $replacements['{Product.name}'] = $orderDetail->product->name;
                $replacements['{Product.reference}'] = $orderDetail->product->reference;
                $replacements['{Product.List.number}'] = $productNumber;
                foreach ($replacements as $search => $replace) {
                    $currentGuaranteeTemplate = str_replace($search, $replace, $currentGuaranteeTemplate);
                }
                $allGuarantee .= '<div class="page-break"></div>';
                $allGuarantee .= $currentGuaranteeTemplate;
                $productNumber++;
            }

            $template .= $currentInvoiceTemplate;
            $template .= '<div class="page-break"></div>';
            $template .= $currentChequeTemplate;
            $template .= $allGuarantee;
        };

        $pdf = App::make('dompdf.wrapper');
        if($order->channel->is_landscape_docs){
            $pdf->setPaper('a4', 'landscape');
        }
        $pdf->loadHTML($template);

        return $pdf->download('ExchangeDocs_'.$productExchange->id.'.pdf');
    }
}
