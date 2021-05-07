<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\Category;
use App\Channel;
use App\Configuration;
use App\ExpenseSettings;
use App\Http\Requests\ReportRequest;
use App\Http\Resources\Categories;
use App\Manufacturer;
use App\Operation;
use App\Order;
use App\OrderComment;
use App\OrderDetail;
use App\OrderDetailState;
use App\Product;
use App\Filters\ProductFilter;
use App\Http\Requests\CsvImportRequest;
use App\Services\CalculateExpenseService\CalculateExpenseService;
use App\UtmCampaign;
use App\UtmGroup;
use App\Vendors\ClickHouse\Client;
use App\VirtualOperation;
use Appwilio\CdekSDK\CdekClient;
use Appwilio\CdekSDK\Common\AdditionalService;
use Appwilio\CdekSDK\Common\ChangePeriod;
use Appwilio\CdekSDK\Common\Order as cdekOrder;
use Appwilio\CdekSDK\Common\State as cdekState;
use Appwilio\CdekSDK\Requests\InfoReportRequest;
use Appwilio\CdekSDK\Requests\StatusReportRequest;
use Carbon\Carbon;
use ChrisKonnertz\StringCalc\StringCalc;
use Excel;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Unirest;
use Doctrine\Common\Annotations\AnnotationRegistry;
use App\TaskState;
use App\User;
use DB;
use App\Role;
use App\Task;
use App\OrderState;
use App\Call;
use App\ModelChange;
use App\CampaignId;

class AnalyticsController extends Controller
{
    /**
     * AnalyticsController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:analytics-report', ['only' => ['report']]);
        $this->middleware(
            'permission:analytics-products-list|analytics-products-list-without-wholesale',
            ['except' => ['report']]
        );
        AnnotationRegistry::registerLoader('class_exists');
        Unirest\Request::timeout(20);
    }

    /**
     * Display the list of products.
     *
     * @param \App\Filters\ProductFilter $filters
     * @param \Illuminate\Http\Request $request
     * @param \App\Product $product
     * @return \Illuminate\Http\Response
     */
    public function productsShow(Product $product, ProductFilter $filters, Request $request)
    {
        return view(
            'analytics.products',
            ['products' => $product->filter($filters)->sortable()->paginate(25)->appends($request->query())]
        );
    }

    /**
     * Display the sales report by brand.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reportSalesByBrands(Request $request)
    {
        $todayCourse = isset($request->count_for_today) ? $request->count_for_today : 0;
         //TODO отрефакторить весь этот спагетти
        $data = [
            'groups' => [],
            'total_rows' => [],
        ];

        if ($request->report) {
            if ($request->submit) {
                $dataFrom = is_null($request->from) ? null : Carbon::createFromFormat(
                    'd-m-Y',
                    $request->from
                )->setTime(0, 0, 0, 0)->toDateTimeString();
                $dataTo = is_null($request->to) ? null : Carbon::createFromFormat('d-m-Y', $request->to)->setTime(
                    23,
                    59,
                    59,
                    0
                )->toDateTimeString();
                $isDeliveryPeriod = is_null($request->is_delivery_period) ? false : $request->is_delivery_period;

                $orders = Order::query()->where('is_hidden', 0);

                if ($isDeliveryPeriod) {
                    if ($dataFrom) {
                        $orders = $orders->where('date_estimated_delivery', '>=', $dataFrom);
                    }
                    if ($dataTo) {
                        $orders = $orders->where('date_estimated_delivery', '<=', $dataTo);
                    }
                } else {
                    if ($dataFrom) {
                        $orders = $orders->where('created_at', '>=', $dataFrom);
                    }
                    if ($dataTo) {
                        $orders = $orders->where('created_at', '<=', $dataTo);
                    }
                }

                if ($request->carriers && is_array($request->carriers)) {
                    $orders = $orders->whereIn('carrier_id', $request->carriers);
                }

                $orders = $orders->get();

                $data = $orders->reduce(
                    function (array $data, Order $order) use ($request, $todayCourse) {
                        $orderDetails = $order->orderDetails->filter(
                            function (OrderDetail $orderDetail) use ($request) {
                                return is_array($request->successful_states) ? in_array(
                                    $orderDetail->currentState()->id,
                                    $request->successful_states
                                ) : false;
                            }
                        );
                        if ($orderDetails->count() < 1) {
                            $orderDetails = $order->orderDetails->filter(
                                function (OrderDetail $orderDetail) use ($request
                                ) {
                                    return is_array($request->minimal_states) ? in_array(
                                            $orderDetail->currentState()->id,
                                            $request->minimal_states
                                        ) && $orderDetail->product->need_guarantee : false;
                                }
                            )->sortBy('price')->slice(0, 1);
                        }
                        if ($orderDetails->count() < 1) {
                            $orderDetails = $order->orderDetails->filter(
                                function (OrderDetail $orderDetail) use ($request
                                ) {
                                    return is_array($request->minimal_states) ? in_array(
                                        $orderDetail->currentState()->id,
                                        $request->minimal_states
                                    ) : false;
                                }
                            )->sortBy('price')->slice(0, 1);
                        }

                        /**
                         * @var \App\OrderDetail $orderDetail
                         */
                        foreach ($orderDetails as $orderDetail) {
                            $data['groups'][$orderDetail->product->manufacturer->name]['rows'][$order->channel->name]['orders'][$order->id] = 1;

                            $data['groups'][$orderDetail->product->manufacturer->name]['rows'][$order->channel->name]['price'] = (float)(isset($data['groups'][$orderDetail->product->manufacturer->name]['rows'][$order->channel->name]['price']) ? $data['groups'][$orderDetail->product->manufacturer->name]['rows'][$order->channel->name]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['groups'][$orderDetail->product->manufacturer->name]['rows'][$order->channel->name]['wholesale_price'] = (float)(isset($data['groups'][$orderDetail->product->manufacturer->name]['rows'][$order->channel->name]['wholesale_price']) ? $data['groups'][$orderDetail->product->manufacturer->name]['rows'][$order->channel->name]['wholesale_price'] + $orderDetail->product->getPrice(!$todayCourse ? $orderDetail->order->created_at : null) * $orderDetail->currency->currency_rate : $orderDetail->product->getPrice(!$todayCourse ? $orderDetail->order->created_at : null) * $orderDetail->currency->currency_rate);

                            $data['groups'][$orderDetail->product->manufacturer->name]['total'][__(
                                'Total'
                            )]['orders'][$order->id] = 1;

                            $data['groups'][$orderDetail->product->manufacturer->name]['total'][__(
                                'Total'
                            )]['price'] = (float)(isset(
                                $data['groups'][$orderDetail->product->manufacturer->name]['total'][__(
                                    'Total'
                                )]['price']
                            ) ? $data['groups'][$orderDetail->product->manufacturer->name]['total'][__(
                                    'Total'
                                )]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['groups'][$orderDetail->product->manufacturer->name]['total'][__(
                                'Total'
                            )]['wholesale_price'] = (float)(isset(
                                $data['groups'][$orderDetail->product->manufacturer->name]['total'][__(
                                    'Total'
                                )]['wholesale_price']
                            ) ? $data['groups'][$orderDetail->product->manufacturer->name]['total'][__(
                                    'Total'
                                )]['wholesale_price'] + $orderDetail->product->getPrice($orderDetail->order->created_at) : $orderDetail->product->getPrice(!$todayCourse ? $orderDetail->order->created_at : null));

                            $data['total_rows'][__('Total for all')]['orders'][$order->id] = 1;

                            $data['total_rows'][__('Total for all')]['price'] = (float)(isset(
                                $data['total_rows'][__(
                                    'Total for all'
                                )]['price']
                            ) ? $data['total_rows'][__(
                                    'Total for all'
                                )]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['total_rows'][__(
                                'Total for all'
                            )]['wholesale_price'] = (float)(isset(
                                $data['total_rows'][__(
                                    'Total for all'
                                )]['wholesale_price']
                            ) ? $data['total_rows'][__(
                                    'Total for all'
                                )]['wholesale_price'] + $orderDetail->product->getPrice(!$todayCourse ? $orderDetail->order->created_at : null) : $orderDetail->product->getPrice(!$todayCourse ? $orderDetail->order->created_at : null));

                            ksort($data['groups'][$orderDetail->product->manufacturer->name]['rows']);
                        }

                        return $data;
                    },
                    $data
                );
            }
            if ($request->save) {
                Configuration::updateOrCreate(
                    ['name' => 'reportSalesByBrands_user_' . \Auth::user()->getAuthIdentifier()],
                    [
                        'values' => json_encode(
                            [
                                'successful_states' => $request->successful_states,
                                'minimal_states' => $request->minimal_states,
                                'carriers' => $request->carriers,
                                'dateFrom' => is_null($request->from) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->from)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'dateTo' => is_null($request->to) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->to)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'isDeliveryPeriod' => $request->is_delivery_period,
                            ]
                        ),
                    ]
                );
            }
            $successful_states = $request->successful_states;
            $minimal_states = $request->minimal_states;
            $carriers = $request->carriers;
            $dateFrom = $request->from;
            $dateTo = $request->to;
            $isDeliveryPeriod = $request->is_delivery_period;
        } else {
            $configuration = Configuration::all()->where(
                'name',
                'reportSalesByBrands_user_' . \Auth::user()->getAuthIdentifier()
            )->first();
            $values = $configuration ? json_decode($configuration->values) : [];
            $successful_states = is_object(
                $values
            ) && isset($values->successful_states) ? $values->successful_states : null;
            $minimal_states = is_object($values) && isset($values->minimal_states) ? $values->minimal_states : [];
            $carriers = is_object($values) && isset($values->carriers) ? $values->carriers : [];
            $dateFrom = is_object($values) && isset($values->dateFrom) && !is_null($values->dateFrom) ? Carbon::now(
            )->addDays($values->dateFrom)->format('d-m-Y') : null;
            $dateTo = is_object($values) && isset($values->dateTo) && !is_null($values->dateTo) ? Carbon::now(
            )->addDays($values->dateTo)->format('d-m-Y') : null;
            $isDeliveryPeriod = is_object(
                $values
            ) && isset($values->isDeliveryPeriod) ? $values->isDeliveryPeriod : null;
        }

        $orderDetailStates = OrderDetailState::pluck('name', 'id');

        return view(
            'analytics.reports.sales.by.brands',
            compact(
                'orderDetailStates',
                'data',
                'successful_states',
                'minimal_states',
                'carriers',
                'dateFrom',
                'dateTo',
                'isDeliveryPeriod',
                'todayCourse'
            )
        );
    }

    /**
     * Display the sales report by products.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reportSalesByProducts(Request $request)
    {
        //TODO отрефакторить весь этот спагетти
        $data = [
            'groups' => [],
            'total_rows' => [],
        ];

        if ($request->report) {
            if ($request->submit) {
                $dataFrom = is_null($request->from) ? null : Carbon::createFromFormat(
                    'd-m-Y',
                    $request->from
                )->setTime(0, 0, 0, 0)->toDateTimeString();
                $dataTo = is_null($request->to) ? null : Carbon::createFromFormat('d-m-Y', $request->to)->setTime(
                    23,
                    59,
                    59,
                    0
                )->toDateTimeString();
                $orders = Order::where('is_hidden', 0);
                if ($dataFrom) {
                    $orders = $orders->where('created_at', '>=', $dataFrom);
                }
                if ($dataTo) {
                    $orders = $orders->where('created_at', '<=', $dataTo);
                }
                if ($request->channel) {
                    $orders = $orders->where('channel_id', $request->channel);
                }
                if ($request->carriers && is_array($request->carriers)) {
                    $orders = $orders->whereIn('carrier_id', $request->carriers);
                }

                $orders = $orders->get();

                $reportProducts = $orders->reduce(
                    function (Collection $reportProducts, Order $order) use ($request) {
                        return $order->orderDetails->reduce(
                            function (
                                Collection $reportProducts,
                                OrderDetail $orderDetail
                            ) use (
                                $request,
                                $order
                            ) {
                                $inProcess = is_array($request->progress_states) && in_array(
                                        $orderDetail->currentState()->id,
                                        $request->progress_states
                                    );

                                if(isset($request->parts_brand_list)) {
                                    if(in_array($orderDetail->product->manufacturer_id, Manufacturer::whereIn('id', $request->parts_brand_list)->pluck('id')->toArray())) {
                                        if($orderDetail->product->is_composite) {
                                            $productParts = [];
                                            foreach (Product::getAllProducts($orderDetail->product) as $product) {
                                                $productParts[$product->name] = [
                                                    'reference' => $product->reference,
                                                    'delivered' => (int)($orderDetail->currentState(
                                                        )->id == $request->successful_state),
                                                    'process_separately' => (int)($inProcess && $order->orderDetails->count(
                                                        ) == 1),
                                                    'process_composition' => (int)($inProcess && $order->orderDetails->count(
                                                        ) > 1),
                                                    'returned' => (int)($orderDetail->currentState(
                                                        )->id == $request->return_state),
                                                ];
                                            }
                                        }
                                    }
                                }

                                $reportProducts->push(
                                    collect(
                                        [
                                            'manufacturer' => $orderDetail->product->manufacturer->name,
                                            'name' => $orderDetail->product->name,
                                            'reference' => $orderDetail->product->reference,
                                            'delivered' => (int)($orderDetail->currentState(
                                                )->id == $request->successful_state),
                                            'process_separately' => (int)($inProcess && $order->orderDetails->count(
                                                ) == 1),
                                            'process_composition' => (int)($inProcess && $order->orderDetails->count(
                                                ) > 1),
                                            'returned' => (int)($orderDetail->currentState(
                                                )->id == $request->return_state),
                                            'parts_of_product' => isset($productParts) ? $productParts : [],
                                        ]
                                    )
                                );

                                return $reportProducts;
                            },
                            $reportProducts
                        );
                    },
                    collect([])
                );

                $reportProducts->groupBy('manufacturer')->each(
                    function (
                        Collection $manufacturerProducts,
                        $manufacturerName
                    ) use (&$data) {
                        $data['groups'][$manufacturerName]['total'][__('Total')] = [
                            'delivered' => $manufacturerProducts->sum('delivered'),
                            'process_separately' => $manufacturerProducts->sum('process_separately'),
                            'process_composition' => $manufacturerProducts->sum('process_composition'),
                            'returned' => $manufacturerProducts->sum('returned'),
                        ];

                        $manufacturerProducts->groupBy('name')->each(
                            function (Collection $products, $productName) use (
                                &$data,
                                $manufacturerName
                            ) {

                                $data['groups'][$manufacturerName]['rows'][$productName] = [
                                    'reference' => $products->first()['reference'],
                                    'delivered' => $products->sum('delivered'),
                                    'process_separately' => $products->sum('process_separately'),
                                    'process_composition' => $products->sum('process_composition'),
                                    'returned' => $products->sum('returned'),
                                ];

                                foreach ($products as $product) {
                                    foreach ($product['parts_of_product'] as $name => $value) {
                                        if(!isset($data['groups'][$manufacturerName]['parts_rows'][$name])) {
                                            $data['groups'][$manufacturerName]['parts_rows'][$name] = [
                                                'reference' => $value['reference'],
                                                'delivered' => $value['delivered'],
                                                'process_separately' => $value['process_separately'],
                                                'process_composition' => $value['process_composition'],
                                                'returned' => $value['returned'],
                                            ];
                                        } else {
                                            $data['groups'][$manufacturerName]['parts_rows'][$name]['delivered'] += $value['delivered'];
                                            $data['groups'][$manufacturerName]['parts_rows'][$name]['process_separately'] += $value['process_separately'];
                                            $data['groups'][$manufacturerName]['parts_rows'][$name]['process_composition'] += $value['process_composition'];
                                            $data['groups'][$manufacturerName]['parts_rows'][$name]['returned'] += $value['returned'];
                                        }

                                        if (array_reduce(
                                                $data['groups'][$manufacturerName]['parts_rows'][$name],
                                                function ($acc, $item) {
                                                    return is_string($item) ? $acc : $acc + $item;
                                                },
                                                0
                                            ) == 0) {
                                            unset($data['groups'][$manufacturerName]['parts_rows'][$name]);
                                        }
                                    }
                                }

                                if (array_reduce(
                                        $data['groups'][$manufacturerName]['rows'][$productName],
                                        function ($acc, $item) {
                                            return is_string($item) ? $acc : $acc + $item;
                                        },
                                        0
                                    ) == 0) {
                                    unset($data['groups'][$manufacturerName]['rows'][$productName]);
                                }
                            }
                        );
                    }
                );

                $data['total_rows'][__('Total for all')] = [
                    'delivered' => $reportProducts->sum('delivered'),
                    'process_separately' => $reportProducts->sum('process_separately'),
                    'process_composition' => $reportProducts->sum('process_composition'),
                    'returned' => $reportProducts->sum('returned'),
                ];

            }


            if ($request->save) {
                Configuration::updateOrCreate(
                    ['name' => 'reportSalesByProducts_user_'.\Auth::user()->getAuthIdentifier()],
                    [
                        'values' => json_encode(
                            [
                                'successful_state' => $request->successful_state,
                                'parts_brand_list' => $request->parts_brand_list,
                                'progress_states' => $request->progress_states,
                                'return_state' => $request->return_state,
                                'channel' => $request->channel,
                                'carriers' => $request->carriers,
                                'dateFrom' => is_null($request->from) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->from)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'dateTo' => is_null($request->to) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->to)->setTime(0, 0, 0, 0),
                                    false
                                ),
                            ]
                        ),
                    ]
                );
            }

            $successful_state = $request->successful_state;
            $progress_states = $request->progress_states;
            $parts_brand_list = $request->parts_brand_list;
            $return_state = $request->return_state;
            $carriers = $request->carriers;
            $channel = $request->channel;
            $dateFrom = $request->from;
            $dateTo = $request->to;

        } else {
            $configuration = Configuration::all()->where(
                'name',
                'reportSalesByProducts_user_'.\Auth::user()->getAuthIdentifier()
            )->first();
            $values = $configuration ? json_decode($configuration->values) : [];
            $successful_state = is_object(
                $values
            ) && isset($values->successful_state) ? $values->successful_state : null;
            $parts_brand_list = is_object($values) && isset($values->parts_brand_list) ? $values->parts_brand_list : [];
            $progress_states = is_object($values) && isset($values->progress_states) ? $values->progress_states : [];
            $return_state = is_object($values) && isset($values->return_state) ? $values->return_state : null;
            $carriers = is_object($values) && isset($values->carriers) ? $values->carriers : [];
            $channel = $values->channel ?? null;
            $dateFrom = is_object($values) && isset($values->dateFrom) && !is_null($values->dateFrom) ? Carbon::now(
            )->addDays($values->dateFrom)->format('d-m-Y') : null;
            $dateTo = is_object($values) && isset($values->dateTo) && !is_null($values->dateTo) ? Carbon::now(
            )->addDays($values->dateTo)->format('d-m-Y') : null;
        }

        $orderDetailStates = OrderDetailState::pluck('name', 'id');

        return view(
            'analytics.reports.sales.by.products',
            compact(
                'orderDetailStates',
                'data',
                'successful_state',
                'progress_states',
                'return_state',
                'carriers',
                'channel',
                'dateFrom',
                'dateTo',
                'parts_brand_list'
            )
        );
    }

    /**
     * Отчет по рекламе с расходами
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \ChrisKonnertz\StringCalc\Exceptions\ContainerException
     * @throws \ChrisKonnertz\StringCalc\Exceptions\NotFoundException
     */
    public function reportAdsByChannelsWithExpenses(Request $request)
    {
        set_time_limit(0);
        $this->validate(
            $request,
            [
                'indicator_min_conversions' => 'integer|min:0|nullable',
                'indicator_clicks_delta' => 'integer|min:0|nullable',
            ]
        );

        $data = [
            'yandex_counters' => [],
            'groups' => [],
            'total_rows' => [],
        ];

        if ($request->report) {
            if ($request->submit) {

                $dataFromCarbon = $request->from ?
                    Carbon::createFromFormat(
                        'd-m-Y',
                        $request->from
                    )
                        ->setTime(0, 0)
                    : null;

                $dataToCarbon = $request->to ?
                    Carbon::createFromFormat(
                        'd-m-Y',
                        $request->to
                    )
                        ->setTime(23, 59, 59)
                    : null;

                $dataFrom = $dataFromCarbon ? $dataFromCarbon->toDateTimeString() : null;
                $dataTo = $dataToCarbon ? $dataToCarbon->toDateTimeString() : null;;


                //region ---- Выборка заказов за период отчета ----

                $orders = Order::where('is_hidden', 0);
                $additionalYandexFilters = [];
                $additionalGoogleFilters = [
                    'is_error_filter' => false,
                    'filters' => [],
                ];

                if ($dataFrom) {
                    $orders = $orders->where('created_at', '>=', $dataFrom);
                }
                if ($dataTo) {
                    $orders = $orders->where('created_at', '<=', $dataTo);
                }

                if ($request->device) {
                    $orders = $orders->where('device', '=', $request->device);
                    $additionalYandexFilters[] = "ym:pv:deviceCategory=='{$request->device}'";
                    $additionalGoogleFilters['filters'][] = [
                        'name' => 'ga:deviceCategory',
                        'expression' => 'EXACT',
                        'values' => [$request->device],
                    ];
                }
                if ($request->age) {
                    $orders = $orders->where('age', '=', $request->age);
                    $additionalYandexFilters[] = "ym:pv:ageInterval=='{$request->age}'";
                    $additionalGoogleFilters['is_error_filter'] = true;
                }
                if ($request->gender) {
                    $orders = $orders->where('gender', '=', $request->gender);
                    $additionalYandexFilters[] = "ym:pv:gender=='{$request->gender}'";
                    $additionalGoogleFilters['is_error_filter'] = true;
                }

                $additionalYandexFilters = $additionalYandexFilters ? implode(',', $additionalYandexFilters) : '';

                $orders = $orders->get();

                //endregion

                $dataFromFormatted = $dataFromCarbon ? $dataFromCarbon->toDateString() : null;
                $dataToFormatted = $dataToCarbon ? $dataToCarbon->toDateString() : null;

                $usdRate = self::getUsdRate();
                //region ---- Получение данных из API счетчиков

                Channel::whereNotNull('go_proxy_url')->get()->each(
                    function (Channel $channel) use (
                        &$data,
                        $dataFromFormatted,
                        $dataToFormatted,
                        $usdRate,
                        $additionalYandexFilters,
                        $additionalGoogleFilters
                    ) {

                        $data['yandex_counters'][$channel->name] = $channel->yandex_counter;

                        $data['groups'][$channel->name] = [
                            'banners' => [],
                            'rows' => [],
                            'totals' => [
                                __('Total').'/google' => [],
                                __('Total').'/yandex' => [],
                            ],
                            'total' => [
                                __('Total') => [],
                            ],
                        ];

                        $dataChannelBanners = &$data['groups'][$channel->name]['banners'];
                        $dataChannelRows = &$data['groups'][$channel->name]['rows'];

                        if ($channel->yandex_counter) {

                            $headers = [];

                            $channel->yandex_token && $headers['Authorization'] = 'OAuth '.$channel->yandex_token;

                            //region ---- Запрос привязки объявлений Яндекс.Директ к метке utm_campaign ----

                            $response = self::requestYandexMetrika(
                                $channel->go_proxy_url,
                                [
                                    'ids' => $channel->yandex_counter,
                                    'date1' => $dataFromFormatted,
                                    'date2' => $dataToFormatted,
                                    'metrics' => 'ym:s:visits',
                                    'dimensions' => 'ym:s:lastDirectClickBanner,ym:s:UTMCampaign',
                                    'group' => 'all',
                                    'quantile' => 100,
                                    'limit' => 100000,
                                    'accuracy' => 'full',
                                ],
                                $headers
                            );

                            $response = self::parseResponseYandexMetrikaOrFail($response);

                            $response && $response->each(
                                function (\stdClass $group) use (&$dataChannelBanners) {

                                    $dimensions = &$group->dimensions;

                                    if (!is_null($dimensions['ym:s:UTMCampaign']->name)
                                        && !is_null($dimensions['ym:s:lastDirectClickBanner']->direct_id)
                                        && !isset($dataChannelBanners[$dimensions['ym:s:lastDirectClickBanner']->direct_id])
                                    ) {
                                        $dataChannelBanners[$dimensions['ym:s:lastDirectClickBanner']->direct_id] = $dimensions['ym:s:UTMCampaign']->name;
                                    }

                                }
                            );

                            $response && $response = $response->groupBy(
                                function (\stdClass $item) {
                                    return $item->dimensions['ym:s:lastDirectClickBanner']->direct_id;
                                }
                            );

                            unset($response);

                            //endregion

                            //region ---- Запрос всех меток зарегистрированных Яндекс.Метрикой за период с показателем отказов ----

                            $response = self::requestYandexMetrika(
                                $channel->go_proxy_url,
                                [
                                    'ids' => $channel->yandex_counter,
                                    'date1' => $dataFromFormatted,
                                    'date2' => $dataToFormatted,
                                    'metrics' => 'ym:s:visits',
                                    'dimensions' => 'ym:s:UTMCampaign,ym:s:UTMSource,ym:s:bounce',
                                    'group' => 'all',
                                    'quantile' => 100,
                                    'limit' => 100000,
                                    'accuracy' => 'full',
                                ],
                                $headers,
                                'stat',
                                $additionalYandexFilters
                            );

                            $response = self::parseResponseYandexMetrikaOrFail($response);

                            $response && $response->each(
                                function (\stdClass $group) use (
                                    $channel,
                                    &$data,
                                    &$dataChannelRows
                                ) {

                                    $groupRow = &$dataChannelRows[$group->dimensions['ym:s:UTMCampaign']->name.'/'.$group->dimensions['ym:s:UTMSource']->name];
                                    $dataChannelTotals = &$data['groups'][$channel->name]['totals'][__(
                                        'Total'
                                    ).'/'.$group->dimensions['ym:s:UTMSource']->name];
                                    $dataChannelTotal = &$data['groups'][$channel->name]['total'][__('Total')];
                                    $dataTotal = &$data['total_rows'][__('Total for all')];

                                    !isset($groupRow['visits']) && $groupRow['visits'] = 0;
                                    !isset($dataChannelTotals['visits']) && $dataChannelTotals['visits'] = 0;
                                    !isset($dataChannelTotal['visits']) && $dataChannelTotal['visits'] = 0;

                                    !isset($groupRow['bounce_visits']) && $groupRow['bounce_visits'] = 0;
                                    !isset($dataChannelTotals['bounce_visits']) && $dataChannelTotals['bounce_visits'] = 0;
                                    !isset($dataChannelTotal['bounce_visits']) && $dataChannelTotal['bounce_visits'] = 0;

                                    !isset($dataTotal['visits']) && $dataTotal['visits'] = 0;
                                    !isset($dataTotal['bounce_visits']) && $dataTotal['bounce_visits'] = 0;

                                    $groupRow['visits'] += (int)$group->metrics['ym:s:visits'];
                                    $dataChannelTotals['visits'] += (int)$group->metrics['ym:s:visits'];
                                    $dataChannelTotal['visits'] += (int)$group->metrics['ym:s:visits'];
                                    $dataTotal['visits'] += (int)$group->metrics['ym:s:visits'];

                                    if ($group->dimensions['ym:s:bounce']->id == 'yes') {
                                        $groupRow['bounce_visits'] += (int)$group->metrics['ym:s:visits'];
                                        $dataChannelTotals['bounce_visits'] += (int)$group->metrics['ym:s:visits'];
                                        $dataChannelTotal['bounce_visits'] += (int)$group->metrics['ym:s:visits'];
                                        $dataTotal['bounce_visits'] += (int)$group->metrics['ym:s:visits'];
                                    }

                                }
                            );

                            unset($response);

                            //endregion

                            //region ---- Получение доступных логинов Яндекс.Директ ----

                            $response = self::requestYandexMetrika(
                                $channel->go_proxy_url,
                                [
                                    'counters' => $channel->yandex_counter,
                                ],
                                $headers,
                                'management'
                            );

                            $clients = self::parseResponseYandexMetrikaOrFail($response, 'management');

                            unset ($response);

                            //endregion

                            //region ---- Получение стоимости кликов Яндекс.Директ в Долларах с переводом в Рубли ----

                            $clients && $clients->each(
                                function (\stdClass $client) use (
                                    $headers,
                                    $channel,
                                    $dataFromFormatted,
                                    $dataToFormatted,
                                    $usdRate,
                                    &$data,
                                    &$dataChannelBanners,
                                    &$dataChannelRows,
                                    $additionalYandexFilters
                                ) {


                                    $response = self::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'direct_client_logins' => $client->chief_login,
                                            'date1' => $dataFromFormatted,
                                            'date2' => $dataToFormatted,
                                            'metrics' => 'ym:ad:USDAdCost,ym:ad:clicks',
                                            'dimensions' => 'ym:ad:directBanner',
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 100000,
                                            'accuracy' => 'full',
                                            'currency' => 'USD'
                                        ],
                                        $headers,
                                        'stat',
                                        $additionalYandexFilters
                                    );

                                    $response = self::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (
                                            $channel,
                                            $usdRate,
                                            &$data,
                                            &$dataChannelBanners,
                                            &$dataChannelRows
                                        ) {
                                            if (count($dataChannelBanners) > 0) {
                                                $utm_campaign = ($dataChannelBanners[$group->dimensions['ym:ad:directBanner']->direct_id] ?? $group->dimensions['ym:ad:directBanner']->direct_id.''.$group->dimensions['ym:ad:directBanner']->name).'/yandex';
                                                $cost = round((float)$group->metrics['ym:ad:USDAdCost'] * $usdRate, 2);
                                                $groupRow = &$dataChannelRows[$utm_campaign];
                                                $dataChannelTotals = &$data['groups'][$channel->name]['totals'][__(
                                                    'Total'
                                                ).'/yandex'];
                                                $dataChannelTotal = &$data['groups'][$channel->name]['total'][__(
                                                    'Total'
                                                )];
                                                $dataTotal = &$data['total_rows'][__('Total for all')];

                                                !isset($groupRow['costs']) && $groupRow['costs'] = 0;
                                                !isset($dataChannelTotals['costs']) && $dataChannelTotals['costs'] = 0;
                                                !isset($dataChannelTotal['costs']) && $dataChannelTotal['costs'] = 0;

                                                !isset($groupRow['clicks']) && $groupRow['clicks'] = 0;
                                                !isset($dataChannelTotals['clicks']) && $dataChannelTotals['clicks'] = 0;
                                                !isset($dataChannelTotal['clicks']) && $dataChannelTotal['clicks'] = 0;

                                                !isset($dataTotal['costs']) && $dataTotal['costs'] = 0;
                                                !isset($dataTotal['clicks']) && $dataTotal['clicks'] = 0;

                                                $groupRow['costs'] += $cost;
                                                $dataChannelTotals['costs'] += $cost;
                                                $dataChannelTotal['costs'] += $cost;

                                                $groupRow['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                                $dataChannelTotals['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                                $dataChannelTotal['clicks'] += (int)$group->metrics['ym:ad:clicks'];

                                                $dataTotal['costs'] += $cost;
                                                $dataTotal['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                            }

                                        }
                                    );

                                    unset($response);

                                }
                            );

                            //endregion

                            //region ---- Если стоимость в долларах не получена - Получение стоимости кликов Яндекс.Директ в Рублях ----

                            $dataChannelYandexTotals = &$data['groups'][$channel->name]['totals'][__(
                                'Total'
                            ).'/yandex'];

                            (!isset($dataChannelYandexTotals['costs']) || !$dataChannelYandexTotals['costs'])
                            && $clients && $clients->each(
                                function (\stdClass $client) use (
                                    $headers,
                                    $channel,
                                    $dataFromFormatted,
                                    $dataToFormatted,
                                    &$data,
                                    &$dataChannelBanners,
                                    &$dataChannelRows,
                                    $additionalYandexFilters
                                ) {


                                    $response = self::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'direct_client_logins' => $client->chief_login,
                                            'date1' => $dataFromFormatted,
                                            'date2' => $dataToFormatted,
                                            'metrics' => 'ym:ad:RUBAdCost,ym:ad:clicks',
                                            'dimensions' => 'ym:ad:directBanner',
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 100000,
                                            'accuracy' => 'full',
                                            'currency' => 'RUB'
                                        ],
                                        $headers,
                                        'stat',
                                        $additionalYandexFilters
                                    );

                                    $response = self::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (
                                            $channel,
                                            &$data,
                                            &$dataChannelBanners,
                                            &$dataChannelRows
                                        ) {

                                            if (isset($dataChannelBanners[$group->dimensions['ym:ad:directBanner']->direct_id])) {

                                                $utm_campaign = $dataChannelBanners[$group->dimensions['ym:ad:directBanner']->direct_id].'/yandex';
                                                $cost = round((float)$group->metrics['ym:ad:RUBAdCost'], 2);
                                                $groupRow = &$dataChannelRows[$utm_campaign];
                                                $dataChannelTotals = &$data['groups'][$channel->name]['totals'][__(
                                                    'Total'
                                                ).'/yandex'];
                                                $dataChannelTotal = &$data['groups'][$channel->name]['total'][__(
                                                    'Total'
                                                )];
                                                $dataTotal = &$data['total_rows'][__('Total for all')];

                                                !isset($groupRow['costs']) && $groupRow['costs'] = 0;
                                                !isset($dataChannelTotals['costs']) && $dataChannelTotals['costs'] = 0;
                                                !isset($dataChannelTotal['costs']) && $dataChannelTotal['costs'] = 0;

                                                !isset($groupRow['clicks']) && $groupRow['clicks'] = 0;
                                                !isset($dataChannelTotals['clicks']) && $dataChannelTotals['clicks'] = 0;
                                                !isset($dataChannelTotal['clicks']) && $dataChannelTotal['clicks'] = 0;

                                                !isset($dataTotal['costs']) && $dataTotal['costs'] = 0;
                                                !isset($dataTotal['clicks']) && $dataTotal['clicks'] = 0;

                                                $groupRow['costs'] += $cost;
                                                $dataChannelTotals['costs'] += $cost;
                                                $dataChannelTotal['costs'] += $cost;

                                                $groupRow['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                                $dataChannelTotals['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                                $dataChannelTotal['clicks'] += (int)$group->metrics['ym:ad:clicks'];

                                                $dataTotal['costs'] += $cost;
                                                $dataTotal['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                            }
                                        }
                                    );

                                    unset($response);

                                }
                            );

                            //endregion


                            if (
                                $channel->google_counter && \Storage::exists(
                                    'keys/google/'.strtolower($channel->name).'.json'
                                )
                                && !$additionalGoogleFilters['is_error_filter']
                            ) {
                                // Create and configure a new client object.
                                $client = new \Google_Client();
                                $client->setApplicationName($channel->name);
                                $client->setAuthConfig(
                                    \Storage::path('keys/google/'.strtolower($channel->name).'.json')
                                );
                                $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
                                $client->setDefer(true);
                                $analytics = new \Google_Service_AnalyticsReporting($client);

                                // Create the DateRange object.
                                $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
                                $dateRange->setStartDate($dataFromFormatted);
                                $dateRange->setEndDate($dataToFormatted);

                                $dimensions = new \Google_Service_AnalyticsReporting_Dimension();
                                $dimensions->setName('ga:adwordsCampaignID');

                                // Create the Metrics object.
                                $costs = new \Google_Service_AnalyticsReporting_Metric();
                                $costs->setExpression("ga:adCost");
                                $costs->setAlias("cost");

                                // Create the Metrics object.
                                $clicks = new \Google_Service_AnalyticsReporting_Metric();
                                $clicks->setExpression("ga:adClicks");
                                $clicks->setAlias("clicks");

                                // Create the ReportRequest object.
                                $request = new \Google_Service_AnalyticsReporting_ReportRequest();
                                $request->setViewId($channel->google_counter);
                                $request->setDateRanges($dateRange);
                                $request->setMetrics([$costs, $clicks]);
                                $request->setDimensions($dimensions);


                                if (count($additionalGoogleFilters['filters'])) {

                                    $dimensionFilterClause = new \Google_Service_AnalyticsReporting_DimensionFilterClause(
                                    );


                                    foreach ($additionalGoogleFilters['filters'] as $goFilter) {
                                        $dimensionFilter = new \Google_Service_AnalyticsReporting_DimensionFilter();
                                        $dimensionFilter->setDimensionName($goFilter['name']);
                                        $dimensionFilter->setOperator($goFilter['expression']);
                                        $dimensionFilter->setExpressions($goFilter['values']);
                                        $dimensionFilterClause->setFilters($dimensionFilter);
                                    }

                                    $request->setDimensionFilterClauses($dimensionFilterClause);
                                }

                                $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
                                $body->setReportRequests([$request]);
                                /**
                                 * @var $google_request \GuzzleHttp\Psr7\Request
                                 */
                                $google_request = $analytics->reports->batchGet($body);
                                $expected_class = $google_request->getHeaderLine('X-Php-Expected-Class');
                                $google_request = $google_request->withoutHeader('X-Php-Expected-Class');
                                $google_request = $google_request->withHeader('Go', (string)$google_request->getUri());
                                $google_request = $google_request->withUri((new Uri($channel->go_proxy_url)));
                                $response = $client->execute($google_request, $expected_class);

                                foreach ($response->getReports() as $report) {
                                    /**
                                     * @var $report \Google_Service_AnalyticsReporting_Report
                                     */
                                    foreach ($report->getData()->getRows() as $row) {
                                        /**
                                         * @var $row \Google_Service_AnalyticsReporting_ReportRow
                                         */
                                        $utm_campaign = $row->getDimensions()[0].'/google';
                                        $campaignName = CampaignId::where('campaign_id',$row->getDimensions()[0])->first();
                                        if(!empty($campaignName)){
                                            $utm_campaign = $campaignName->utm_campaign->name.'/google';
                                        }
                                        /**
                                         * @var $metric \Google_Service_AnalyticsReporting_DateRangeValues
                                         */
                                        $metric = $row->getMetrics()[0];
                                        $cost = round((float)$metric->getValues()[0] * 1.2 * $usdRate, 2);
                                        $data['groups'][$channel->name]['rows'][$utm_campaign]['costs'] = isset($data['groups'][$channel->name]['rows'][$utm_campaign]['costs']) ? (float)$data['groups'][$channel->name]['rows'][$utm_campaign]['costs'] + $cost : $cost;
                                        $data['groups'][$channel->name]['totals'][__(
                                            'Total'
                                        ).'/google']['costs'] = isset(
                                            $data['groups'][$channel->name]['totals'][__(
                                                'Total'
                                            ).'/google']['costs']
                                        ) ? (float)$data['groups'][$channel->name]['totals'][__(
                                                'Total'
                                            ).'/google']['costs'] + $cost : $cost;
                                        $data['groups'][$channel->name]['total'][__(
                                            'Total'
                                        )]['costs'] = isset(
                                            $data['groups'][$channel->name]['total'][__(
                                                'Total'
                                            )]['costs']
                                        ) ? (float)$data['groups'][$channel->name]['total'][__(
                                                'Total'
                                            )]['costs'] + $cost : $cost;

                                        $data['total_rows'][__(
                                            'Total for all'
                                        )]['costs'] = isset(
                                            $data['total_rows'][__(
                                                'Total for all'
                                            )]['costs']
                                        ) ? (float)$data['total_rows'][__('Total for all')]['costs'] + $cost : $cost;

                                        $clicks_per_utm = $metric->getValues()[1];
                                        $data['groups'][$channel->name]['rows'][$utm_campaign]['clicks'] = isset($data['groups'][$channel->name]['rows'][$utm_campaign]['clicks']) ? (float)$data['groups'][$channel->name]['rows'][$utm_campaign]['clicks'] + $clicks_per_utm : $clicks_per_utm;
                                        $data['groups'][$channel->name]['totals'][__(
                                            'Total'
                                        ).'/google']['clicks'] = isset(
                                            $data['groups'][$channel->name]['totals'][__(
                                                'Total'
                                            ).'/google']['clicks']
                                        ) ? (float)$data['groups'][$channel->name]['totals'][__(
                                                'Total'
                                            ).'/google']['clicks'] + $clicks_per_utm : $clicks_per_utm;
                                        $data['groups'][$channel->name]['total'][__(
                                            'Total'
                                        )]['clicks'] = isset(
                                            $data['groups'][$channel->name]['total'][__(
                                                'Total'
                                            )]['clicks']
                                        ) ? (float)$data['groups'][$channel->name]['total'][__(
                                                'Total'
                                            )]['clicks'] + $clicks_per_utm : $clicks_per_utm;

                                        $data['total_rows'][__(
                                            'Total for all'
                                        )]['clicks'] = isset(
                                            $data['total_rows'][__(
                                                'Total for all'
                                            )]['clicks']
                                        ) ? (float)$data['total_rows'][__(
                                                'Total for all'
                                            )]['clicks'] + $clicks_per_utm : $clicks_per_utm;

                                    }
                                }
                            }
                        }

                    }
                );

                $configuration = Configuration::all()->where(
                    'name',
                    'settings_order_detail_states_for_expenses'
                )->first();
                $values = $configuration ? json_decode($configuration->values) : [];
                $successful_states = $values->successful_states ?? [];
                $minimal_states = is_object($values) && isset($values->minimal_states) ? $values->minimal_states : [];

                //endregion
                $orders->each(
                    function (Order $order) use (&$data, $successful_states, $minimal_states) {
                        $orderDetails = $order->orderDetails->filter(
                            function (OrderDetail $orderDetail) use ($successful_states) {
                                return in_array($orderDetail->currentState()->id, ($successful_states ?? []));
                            }
                        );
                        if ($orderDetails->count() < 1) {
                            $orderDetails = $order->orderDetails->filter(
                                function (OrderDetail $orderDetail) use ($minimal_states) {
                                    return is_array($minimal_states) ? in_array(
                                            $orderDetail->currentState()->id,
                                            $minimal_states
                                        ) && $orderDetail->product->need_guarantee : false;
                                }
                            )->sortBy('price')->slice(0, 1);
                        }
                        if ($orderDetails->count() < 1) {
                            $orderDetails = $order->orderDetails->filter(
                                function (OrderDetail $orderDetail) use ($minimal_states) {
                                    return is_array($minimal_states) ? in_array(
                                        $orderDetail->currentState()->id,
                                        $minimal_states
                                    ) : false;
                                }
                            )->sortBy('price')->slice(0, 1);
                        }

                        $utm_campaign = $order->utm_campaign ? $order->utm_campaign.'/'.$order->utm_source : ($order->search_query ? 'search_query/'.$order->search_query : 'utm no');
                        $utm_source = $order->utm_campaign ? '/'.$order->utm_source : ($order->search_query ? '/'.$order->search_query : '/utm no');
                        $data['groups'][$order->channel->name]['rows'][$utm_campaign]['orders'][$order->id] = 1;
                        $data['groups'][$order->channel->name]['totals'][__(
                            'Total'
                        ).$utm_source]['orders'][$order->id] = 1;
                        $data['groups'][$order->channel->name]['total'][__('Total')]['orders'][$order->id] = 1;
                        $data['total_rows'][__('Total for all')]['orders'][$order->id] = 1;

                        if ($orderDetails->count() > 0) {
                            $data['groups'][$order->channel->name]['rows'][$utm_campaign]['successful_orders'][$order->id] = 1;
                            $data['groups'][$order->channel->name]['totals'][__(
                                'Total'
                            ).$utm_source]['successful_orders'][$order->id] = 1;
                            $data['groups'][$order->channel->name]['total'][__(
                                'Total'
                            )]['successful_orders'][$order->id] = 1;
                            $data['total_rows'][__('Total for all')]['successful_orders'][$order->id] = 1;
                        }

                        /**
                         * @var \App\OrderDetail $orderDetail
                         */
                        foreach ($orderDetails as $orderDetail) {

                            $data['groups'][$order->channel->name]['rows'][$utm_campaign]['price'] = (float)(isset($data['groups'][$order->channel->name]['rows'][$utm_campaign]['price']) ? $data['groups'][$order->channel->name]['rows'][$utm_campaign]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['groups'][$order->channel->name]['totals'][__(
                                'Total'
                            ).$utm_source]['price'] = (float)(isset(
                                $data['groups'][$order->channel->name]['totals'][__(
                                    'Total'
                                ).$utm_source]['price']
                            ) ? $data['groups'][$order->channel->name]['totals'][__(
                                    'Total'
                                ).$utm_source]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['groups'][$order->channel->name]['total'][__(
                                'Total'
                            )]['price'] = (float)(isset(
                                $data['groups'][$order->channel->name]['total'][__(
                                    'Total'
                                )]['price']
                            ) ? $data['groups'][$order->channel->name]['total'][__(
                                    'Total'
                                )]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['total_rows'][__('Total for all')]['price'] = (float)(isset(
                                $data['total_rows'][__(
                                    'Total for all'
                                )]['price']
                            ) ? $data['total_rows'][__(
                                    'Total for all'
                                )]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                        }

                        ksort($data['groups'][$order->channel->name]['rows']);

                    }
                );
            }
            if ($request->save) {
                Configuration::updateOrCreate(
                    ['name' => 'reportAdsByChannels_user_'.\Auth::user()->getAuthIdentifier() . '_expenses'],
                    [
                        'values' => json_encode(
                            [
                                'dateFrom' => is_null($request->from) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->from)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'dateTo' => is_null($request->to) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->to)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'device' => $request->device,
                                'age' => $request->age,
                                'gender' => $request->gender,
                                'indicator_min_conversions' => $request->indicator_min_conversions,
                                'indicator_clicks_delta' => $request->indicator_clicks_delta,
                                'show_utm' => $request->show_utm ? $request->show_utm : 0,
                                'count_for_today' => $request->count_for_today ? $request->count_for_today : 0,
                                'compare_categories' => $request->compare_categories ? $request->compare_categories : 0,
                            ]
                        ),
                    ]
                );
            }
            $carriers = $request->carriers;
            $dateFrom = $request->from;
            $dateTo = $request->to;
            $device = $request->device;
            $age = $request->age;
            $gender = $request->gender;
            $indicator_min_conversions = $request->indicator_min_conversions;
            $indicator_clicks_delta = $request->indicator_clicks_delta;
        } else {
            $configuration = Configuration::all()->where(
                'name',
                'reportAdsByChannels_user_'.\Auth::user()->getAuthIdentifier() . '_expenses'
            )->first();
            $values = $configuration ? json_decode($configuration->values) : [];
            $carriers = is_object($values) && isset($values->carriers) ? $values->carriers : [];
            $dateFrom = is_object($values) && isset($values->dateFrom) && !is_null($values->dateFrom) ? Carbon::now(
            )->addDays($values->dateFrom)->format('d-m-Y') : null;
            $dateTo = is_object($values) && isset($values->dateTo) && !is_null($values->dateTo) ? Carbon::now(
            )->addDays($values->dateTo)->format('d-m-Y') : null;
            $device = is_object($values) && isset($values->device) ? $values->device : null;
            $age = is_object($values) && isset($values->age) ? $values->age : null;
            $gender = is_object($values) && isset($values->gender) ? $values->gender : null;
            $indicator_min_conversions = is_object(
                $values
            ) && isset($values->indicator_min_conversions) ? $values->indicator_min_conversions : null;
            $indicator_clicks_delta = is_object(
                $values
            ) && isset($values->indicator_clicks_delta) ? $values->indicator_clicks_delta : null;
            $utm = isset($values->show_utm) ? $values->show_utm : 0;
            $todayCourse = isset($values->count_for_today) ? $values->count_for_today : 0;
            $cmpCategories = isset($values->compare_categories) ? $values->compare_categories : 0;
        }

        $devices = [
            0 => 'Все',
            'mobile' => 'Мобильные',
            'tablet' => 'Планшеты',
            'desktop' => 'Десктопы',
        ];
        $ages = [
            0 => 'Все',
            '17' => 'Младше 18',
            '18' => 'От 18 до 24',
            '25' => 'От 25 до 34',
            '35' => 'От 35 до 44',
            '45' => 'От 45 до 54',
            '55' => '55 и старше',
        ];
        $genders = [
            0 => 'Все',
            'female' => 'Женщины',
            'male' => 'Мужчины',
        ];
        $orderDetailStates = OrderDetailState::pluck('name', 'id');

        $dataUtmGroups = [];

        foreach ($data['groups'] as $dataGroupName => &$dataGroup) {
            $dataGroup['rows'] = $dataGroup['rows'] ?? [];

            foreach ($dataGroup['rows'] as $rowName => &$dataRow) {

                $utms = explode('/', $rowName);
                $utmCampaign = $utms[0] ?? '###';
                $utmSource = $utms[1] ?? '';

                $sqlDates = [];

                $dataFromCarbon = $dataFromCarbon ?? null;
                $dataToCarbon = $dataToCarbon ?? null;

                if (!is_null($dataFromCarbon)) {
                    $sqlDates[] = "Date >= '{$dataFromCarbon->format('Y-m-d')}'";
                }

                if (!is_null($dataToCarbon)) {
                    $sqlDates[] = "Date <= '{$dataToCarbon->format('Y-m-d')}'";
                }

                $uniqueClientIDQuantity = isset($data['yandex_counters'][$dataGroupName]) ?
                    $this
                        ->getClientsQuantityFromMetrikaLogs(
                            $data['yandex_counters'][$dataGroupName],
                            array_merge(
                                [
                                    'ClientID > 0',
                                    "UTMCampaign = '{$utmCampaign}'",
                                    "UTMSource = '{$utmSource}'",
                                ],
                                $sqlDates
                            )
                        ) : 0;

                $zeroClientIDQuantity = isset($data['yandex_counters'][$dataGroupName]) ?
                    $this
                        ->getClientsQuantityFromMetrikaLogs(
                            $data['yandex_counters'][$dataGroupName],
                            array_merge(
                                [
                                    'ClientID = 0',
                                    "UTMCampaign = '{$utmCampaign}'",
                                    "UTMSource = '{$utmSource}'",
                                ],
                                $sqlDates
                            ),
                            false
                        ) : 0;

                $dataRow['unique_client_id'] = $uniqueClientIDQuantity;
                $dataRow['zero_client_id'] = $zeroClientIDQuantity;

                if (isset(
                    $data['groups'][$dataGroupName]['totals'][__(
                        'Total'
                    ).'/'.$utmSource]
                )) {
                    $data['groups'][$dataGroupName]['totals'][__(
                        'Total'
                    ).'/'.$utmSource]['unique_client_id'] = ($data['groups'][$dataGroupName]['totals'][__(
                                'Total'
                            ).'/'.$utmSource]['unique_client_id'] ?? 0) + $uniqueClientIDQuantity;
                    $data['groups'][$dataGroupName]['totals'][__(
                        'Total'
                    ).'/'.$utmSource]['zero_client_id'] = ($data['groups'][$dataGroupName]['totals'][__(
                                'Total'
                            ).'/'.$utmSource]['zero_client_id'] ?? 0) + $zeroClientIDQuantity;
                }

                if (isset($data['total_rows'][__('Total for all')])) {
                    $data['total_rows'][__('Total for all')]['unique_client_id'] = ($data['total_rows'][__(
                                'Total for all'
                            )]['unique_client_id'] ?? 0) + $uniqueClientIDQuantity;
                    $data['total_rows'][__('Total for all')]['zero_client_id'] = ($data['total_rows'][__(
                                'Total for all'
                            )]['zero_client_id'] ?? 0) + $zeroClientIDQuantity;
                }

                $dataRow['utm_groups'] = UtmGroup::getGroupsForString("::{$dataGroupName}::{$rowName}");
                $dataRow['clicks'] = isset($dataRow['clicks']) ? $dataRow['clicks'] : 0;
                $dataRow['costs'] = isset($dataRow['costs']) ? $dataRow['costs'] : 0;
                $dataRow['visits'] = isset($dataRow['visits']) ? $dataRow['visits'] : 0;
                $dataRow['bounce_visits'] = isset($dataRow['bounce_visits']) ? $dataRow['bounce_visits'] : 0;
                $dataRow['orders'] = isset($dataRow['orders']) ? $dataRow['orders'] : [];
                $dataRow['successful_orders'] = isset($dataRow['successful_orders']) ? $dataRow['successful_orders'] : [];
                $dataRow['price'] = isset($dataRow['price']) ? $dataRow['price'] : 0;


                /**
                 * @var UtmGroup $utmGroup
                 */
                foreach ($dataRow['utm_groups'] as $utmGroup) {
                    if (!isset($dataUtmGroups[$utmGroup->name])) {
                        $dataUtmGroups[$utmGroup->name] = [
                            'clicks' => 0,
                            'costs' => 0,
                            'visits' => 0,
                            'bounce_visits' => 0,
                            'orders' => [],
                            'successful_orders' => [],
                            'price' => 0,
                            'utmGroup' => $utmGroup,
                            'unique_client_id' => 0,
                            'zero_client_id' => 0,
                        ];
                    }

                    $dataUtmGroups[$utmGroup->name] = [
                        'clicks' => $dataUtmGroups[$utmGroup->name]['clicks'] + $dataRow['clicks'],
                        'costs' => $dataUtmGroups[$utmGroup->name]['costs'] + $dataRow['costs'],
                        'visits' => $dataUtmGroups[$utmGroup->name]['visits'] + $dataRow['visits'],
                        'bounce_visits' => $dataUtmGroups[$utmGroup->name]['bounce_visits'] + $dataRow['bounce_visits'],
                        'orders' => array_merge($dataUtmGroups[$utmGroup->name]['orders'], $dataRow['orders']),
                        'successful_orders' => array_merge(
                            $dataUtmGroups[$utmGroup->name]['successful_orders'],
                            $dataRow['successful_orders']
                        ),
                        'price' => $dataUtmGroups[$utmGroup->name]['price'] + $dataRow['price'],
                        'utmGroup' => $dataUtmGroups[$utmGroup->name]['utmGroup'],
                        'unique_client_id' => $dataUtmGroups[$utmGroup->name]['unique_client_id'] + $uniqueClientIDQuantity,
                        'zero_client_id' => $dataUtmGroups[$utmGroup->name]['zero_client_id'] + $zeroClientIDQuantity,
                    ];
                }
            }
        }

        $dataFromCarbon = $dateFrom ?
            Carbon::createFromFormat(
                'd-m-Y',
                $dateFrom
            )
                ->setTime(0, 0)
            : null;

        $dataToCarbon = $dateTo ?
            Carbon::createFromFormat(
                'd-m-Y',
                $dateTo
            )
                ->setTime(23, 59, 59)
            : null;

        $reportDays = !is_null($dataFromCarbon) && !is_null($dataToCarbon) ? $dataToCarbon->diffInDays(
                $dataFromCarbon
            ) + 1 : 0;

        $dataUtmGroups = Arr::sort(
            $dataUtmGroups,
            function ($row) {
                return $row['utmGroup']->sort_order;
            }
        );

        //Флаг отображения utm групп
        //Если не достали по умолчанию
        if(!isset($utm)) {
            $utm = isset($request->show_utm) ? $request->show_utm : 0;
        }

        //Флаг "считать по сегодняшнему курсу"
        //Если не достали по умолчанию
        if(!isset($todayCourse)) {
            $todayCourse = isset($request->count_for_today) ? $request->count_for_today : 0;
        }

        //Флаг отображения трафика с поисковых систем
        $traffic = isset($request->show_traffic) ? $request->show_traffic : 0;

        //Флаг соединения по категориям
        //Если не достали по умолчанию
        if(!isset($cmpCategories)) {
            $cmpCategories = isset($request->compare_categories) ? $request->compare_categories : 1;
        }

        //Расчет прибыли по каждой метке
        $calculateService = new CalculateExpenseService($todayCourse, $cmpCategories);
        foreach ($data['groups'] as $groupName => &$group) {
            foreach ($group['rows'] as $rowName => &$row) {
                $row['row_profit'] = $calculateService->calculateUtm(isset($row['orders']) ? $row['orders'] : 0, $row['costs']);
                $utmGroups = UtmGroup::getGroupsForString($rowName);
                foreach ($utmGroups as $utmGroup) {
                    //UTM Группы
                    isset($dataUtmGroups[$utmGroup->name]['total_expenses'])
                        ? $dataUtmGroups[$utmGroup->name]['total_expenses'] += $row['row_profit']['expense_sum']
                        : $dataUtmGroups[$utmGroup->name]['total_expenses'] = $row['row_profit']['expense_sum'];
                    if(!empty($row['row_profit']['expenses'])) {
                        foreach( $row['row_profit']['expenses'] as $expense) {
                            $dataUtmGroups[$utmGroup->name]['expenses'][] = $expense;
                        }
                        $dataUtmGroups[$utmGroup->name]['expenses'] = CalculateExpenseService::sumDuplicated($dataUtmGroups[$utmGroup->name]['expenses']);
                    }
                    isset($dataUtmGroups[$utmGroup->name]['profit'])
                        ? $dataUtmGroups[$utmGroup->name]['profit'] += $row['row_profit']['profit']
                        : $dataUtmGroups[$utmGroup->name]['profit'] = $row['row_profit']['profit'];
                }
                //Итого по всем
                isset($data['total_rows'][__('Total for all')]['total_expenses'])
                    ? $data['total_rows'][__('Total for all')]['total_expenses'] += $row['row_profit']['expense_sum']
                    : $data['total_rows'][__('Total for all')]['total_expenses'] = $row['row_profit']['expense_sum'];
                if(!empty($row['row_profit']['expenses'])) {
                    foreach( $row['row_profit']['expenses'] as $expense) {
                        $data['total_rows'][__('Total for all')]['expenses'][] = $expense;
                    }
                    $data['total_rows'][__('Total for all')]['expenses'] = CalculateExpenseService::sumDuplicated($data['total_rows'][__('Total for all')]['expenses']);
                }
                isset($data['total_rows'][__('Total for all')]['profit'])
                    ? $data['total_rows'][__('Total for all')]['profit'] += $row['row_profit']['profit']
                    : $data['total_rows'][__('Total for all')]['profit'] = $row['row_profit']['profit'];
                //Итого
                isset($group['total'][__('Total')]['total_expenses'])
                    ? $group['total'][__('Total')]['total_expenses'] += $row['row_profit']['expense_sum']
                    : $group['total'][__('Total')]['total_expenses'] = $row['row_profit']['expense_sum'];
                if(!empty($row['row_profit']['expenses'])) {
                    foreach( $row['row_profit']['expenses'] as $expense) {
                        $group['total'][__('Total')]['expenses'][] = $expense;
                    }
                    $group['total'][__('Total')]['expenses'] = CalculateExpenseService::sumDuplicated($group['total'][__('Total')]['expenses']);
                }
                isset($group['total'][__('Total')]['profit'])
                    ? $group['total'][__('Total')]['profit'] += $row['row_profit']['profit']
                    : $group['total'][__('Total')]['profit'] = $row['row_profit']['profit'];
                //Итого/{$utm_group}
                if (stripos($rowName, 'google')) {
                    isset($group['totals'][__('Total') . '/google']['total_expenses'])
                        ? $group['totals'][__('Total') . '/google']['total_expenses'] += $row['row_profit']['expense_sum']
                        : $group['totals'][__('Total') . '/google']['total_expenses'] = $row['row_profit']['expense_sum'];
                    if(!empty($row['row_profit']['expenses'])) {
                        foreach( $row['row_profit']['expenses'] as $expense) {
                            $group['totals'][__('Total') . '/google']['expenses'][] = $expense;
                        }
                        $group['totals'][__('Total') . '/google']['expenses'] = CalculateExpenseService::sumDuplicated($group['totals'][__('Total') . '/google']['expenses']);
                    }
                    isset($group['totals'][__('Total') . '/google']['profit'])
                        ? $group['totals'][__('Total') . '/google']['profit'] += $row['row_profit']['profit']
                        : $group['totals'][__('Total') . '/google']['profit'] = $row['row_profit']['profit'];
                } elseif (stripos($rowName, 'organic') !== false) {
                    isset($group['totals'][__('Total') . '/organic']['total_expenses'])
                        ? $group['totals'][__('Total') . '/organic']['total_expenses'] += $row['row_profit']['expense_sum']
                        : $group['totals'][__('Total') . '/organic']['total_expenses'] = $row['row_profit']['expense_sum'];
                    if(!empty($row['row_profit']['expenses'])) {
                        foreach( $row['row_profit']['expenses'] as $expense) {
                            $group['totals'][__('Total') . '/organic']['expenses'][] = $expense;
                        }
                        $group['totals'][__('Total') . '/organic']['expenses'] = CalculateExpenseService::sumDuplicated($group['totals'][__('Total') . '/organic']['expenses']);
                    }
                    isset($group['totals'][__('Total') . '/organic']['profit'])
                        ? $group['totals'][__('Total') . '/organic']['profit'] += $row['row_profit']['profit']
                        : $group['totals'][__('Total') . '/organic']['profit'] = $row['row_profit']['profit'];
                } elseif (stripos($rowName, 'market.yandex.ru')) {
                    isset($group['totals'][__('Total') . '/market.yandex.ru']['total_expenses'])
                        ? $group['totals'][__('Total') . '/market.yandex.ru']['total_expenses'] += $row['row_profit']['expense_sum']
                        : $group['totals'][__('Total') . '/market.yandex.ru']['total_expenses'] = $row['row_profit']['expense_sum'];
                    if(!empty($row['row_profit']['expenses'])) {
                        foreach( $row['row_profit']['expenses'] as $expense) {
                            $group['totals'][__('Total') . '/market.yandex.ru']['expenses'][] = $expense;
                        }
                        $group['totals'][__('Total') . '/market.yandex.ru']['expenses'] = CalculateExpenseService::sumDuplicated($group['totals'][__('Total') . '/market.yandex.ru']['expenses']);
                    }
                    isset($group['totals'][__('Total') . '/market.yandex.ru']['profit'])
                        ? $group['totals'][__('Total') . '/market.yandex.ru']['profit'] += $row['row_profit']['profit']
                        : $group['totals'][__('Total') . '/market.yandex.ru']['profit'] = $row['row_profit']['profit'];
                } elseif ($rowName == 'utm no') {
                    isset($group['totals'][__('Total') . '/utm no']['total_expenses'])
                        ? $group['totals'][__('Total') . '/utm no']['total_expenses'] += $row['row_profit']['expense_sum']
                        : $group['totals'][__('Total') . '/utm no']['total_expenses'] = $row['row_profit']['expense_sum'];
                    if(!empty($row['row_profit']['expenses'])) {
                        foreach( $row['row_profit']['expenses'] as $expense) {
                            $group['totals'][__('Total') . '/utm no']['expenses'][] = $expense;
                        }
                        $group['totals'][__('Total') . '/utm no']['expenses'] = CalculateExpenseService::sumDuplicated($group['totals'][__('Total') . '/utm no']['expenses']);
                    }
                    isset($group['totals'][__('Total') . '/utm no']['profit'])
                        ? $group['totals'][__('Total') . '/utm no']['profit'] += $row['row_profit']['profit']
                        : $group['totals'][__('Total') . '/utm no']['profit'] = $row['row_profit']['profit'];
                } elseif (stripos($rowName, 'yandex')) {
                    isset($group['totals'][__('Total') . '/yandex']['total_expenses'])
                        ? $group['totals'][__('Total') . '/yandex']['total_expenses'] += $row['row_profit']['expense_sum']
                        : $group['totals'][__('Total') . '/yandex']['total_expenses'] = $row['row_profit']['expense_sum'];
                    if(!empty($row['row_profit']['expenses'])) {
                        foreach( $row['row_profit']['expenses'] as $expense) {
                            $group['totals'][__('Total') . '/yandex']['expenses'][] = $expense;
                        }
                        $group['totals'][__('Total') . '/yandex']['expenses'] = CalculateExpenseService::sumDuplicated($group['totals'][__('Total') . '/yandex']['expenses']);
                    }
                    isset($group['totals'][__('Total') . '/yandex']['profit'])
                        ? $group['totals'][__('Total') . '/yandex']['profit'] += $row['row_profit']['profit']
                        : $group['totals'][__('Total') . '/yandex']['profit'] = $row['row_profit']['profit'];
                } else {
                    isset($group['totals'][__('Total') . '/']['total_expenses'])
                        ? $group['totals'][__('Total') . '/']['total_expenses'] += $row['row_profit']['expense_sum']
                        : $group['totals'][__('Total') . '/']['total_expenses'] = $row['row_profit']['expense_sum'];
                    if(!empty($row['row_profit']['expenses'])) {
                        foreach( $row['row_profit']['expenses'] as $expense) {
                            $group['totals'][__('Total') . '/']['expenses'][] = $expense;
                        }
                        $group['totals'][__('Total') . '/']['expenses'] = CalculateExpenseService::sumDuplicated($group['totals'][__('Total') . '/']['expenses']);
                    }
                    isset($group['totals'][__('Total') . '/']['profit'])
                        ? $group['totals'][__('Total') . '/']['profit'] += $row['row_profit']['profit']
                        : $group['totals'][__('Total') . '/']['profit'] = $row['row_profit']['profit'];
                }
            }
        }

        foreach ($data['groups'] as $groupName => &$group) {
            $group['rows'] = $this->splitRowsInSameNames($group['rows']);
        }

        return view(
            'analytics.reports.ads.expense.channels',
            compact(
                'orderDetailStates',
                'data',
                'successful_states',
                'minimal_states',
                'carriers',
                'dateFrom',
                'dateTo',
                'device',
                'devices',
                'age',
                'ages',
                'utm',
                'traffic',
                'gender',
                'genders',
                'indicator_min_conversions',
                'indicator_clicks_delta',
                'dataUtmGroups',
                'reportDays',
                'todayCourse',
                'cmpCategories'
            )
        );
    }

    /**
     * Метод для склейки utm в виду разных названий
     *
     * @param array $group
     * @return array
     */
    protected function splitRowsInSameNames(array $group)
    {
        try {
            $newGroup = [];
            foreach ($group as $name => $value) {
                if (in_array($name, array_keys($newGroup))) {
                    $newGroup[strtolower($name)]['visits'] += $value['visits'];
                    $newGroup[strtolower($name)]['bounce_visits'] += $value['bounce_visits'];
                    $newGroup[strtolower($name)]['costs'] += $value['costs'];
                    $newGroup[strtolower($name)]['clicks'] += $value['clicks'];
                    $newGroup[strtolower($name)]['unique_client_id'] += $value['unique_client_id'];
                    $newGroup[strtolower($name)]['zero_client_id'] += $value['zero_client_id'];
                    $newGroup[strtolower($name)]['utm_groups'] = $newGroup[strtolower($name)]['utm_groups']->merge($value['utm_groups']);
                    foreach ($value['orders'] as $key => $order) {
                        $newGroup[strtolower($name)]['orders'][$key] = $order;
                    }
                    foreach ($value['successful_orders'] as $key => $order) {
                        $newGroup[strtolower($name)]['successful_orders'][$key] = $order;
                    }
                    $newGroup[strtolower($name)]['price'] += $value['price'];
                    $newGroup[strtolower($name)]['row_profit']['profit'] += $value['row_profit']['profit'];
                    $newGroup[strtolower($name)]['row_profit']['expense_sum'] += $value['row_profit']['expense_sum'];

                    foreach ($value['row_profit']['expenses'] as $expens) {
                        $newGroup[strtolower($name)]['row_profit']['expenses'][] = $expens;
                    }
                    $newGroup[strtolower($name)]['row_profit']['expenses'] = CalculateExpenseService::sumDuplicated($newGroup[strtolower($name)]['row_profit']['expenses']);
                } else {
                    $newGroup[strtolower($name)] = $value;
                }
            }

            return $newGroup;
        } catch (\Exception $exception) {
            \Session::flash('warning', (__('Error gluing utm campaigns : :error', ['error' => $exception->getMessage()])));
            return $group;
        }
    }

    /**
     * Отображение рекламного отчета по источникам.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reportAdsByChannels(Request $request)
    {

        $this->validate(
            $request,
            [
                'indicator_min_conversions' => 'integer|min:0|nullable',
                'indicator_clicks_delta' => 'integer|min:0|nullable',
            ]
        );

        $data = [
            'yandex_counters' => [],
            'groups' => [],
            'total_rows' => [],
        ];

        if ($request->report) {
            if ($request->submit) {

                $dataFromCarbon = $request->from ?
                    Carbon::createFromFormat(
                        'd-m-Y',
                        $request->from
                    )
                        ->setTime(0, 0)
                    : null;

                $dataToCarbon = $request->to ?
                    Carbon::createFromFormat(
                        'd-m-Y',
                        $request->to
                    )
                        ->setTime(23, 59, 59)
                    : null;

                $dataFrom = $dataFromCarbon ? $dataFromCarbon->toDateTimeString() : null;
                $dataTo = $dataToCarbon ? $dataToCarbon->toDateTimeString() : null;;


                //region ---- Выборка заказов за период отчета ----

                $orders = Order::where('is_hidden', 0);
                $additionalYandexFilters = [];
                $additionalGoogleFilters = [
                    'is_error_filter' => false,
                    'filters' => [],
                ];

                if ($dataFrom) {
                    $orders = $orders->where('created_at', '>=', $dataFrom);
                }
                if ($dataTo) {
                    $orders = $orders->where('created_at', '<=', $dataTo);
                }
                if ($request->device) {
                    $orders = $orders->where('device', '=', $request->device);
                    $additionalYandexFilters[] = "ym:pv:deviceCategory=='{$request->device}'";
                    $additionalGoogleFilters['filters'][] = [
                        'name' => 'ga:deviceCategory',
                        'expression' => 'EXACT',
                        'values' => [$request->device],
                    ];
                }
                if ($request->age) {
                    $orders = $orders->where('age', '=', $request->age);
                    $additionalYandexFilters[] = "ym:pv:ageInterval=='{$request->age}'";
                    $additionalGoogleFilters['is_error_filter'] = true;
                }
                if ($request->gender) {
                    $orders = $orders->where('gender', '=', $request->gender);
                    $additionalYandexFilters[] = "ym:pv:gender=='{$request->gender}'";
                    $additionalGoogleFilters['is_error_filter'] = true;
                }

                $additionalYandexFilters = $additionalYandexFilters ? implode(',', $additionalYandexFilters) : '';

                $orders = $orders->get();

                //endregion

                $dataFromFormatted = $dataFromCarbon ? $dataFromCarbon->toDateString() : null;
                $dataToFormatted = $dataToCarbon ? $dataToCarbon->toDateString() : null;

                $usdRate = self::getUsdRate();

                //region ---- Получение данных из API счетчиков

                Channel::whereNotNull('go_proxy_url')->get()->each(
                    function (Channel $channel) use (
                        &$data,
                        $dataFromFormatted,
                        $dataToFormatted,
                        $usdRate,
                        $additionalYandexFilters,
                        $additionalGoogleFilters
                    ) {

                        $data['yandex_counters'][$channel->name] = $channel->yandex_counter;

                        $data['groups'][$channel->name] = [
                            'banners' => [],
                            'rows' => [],
                            'totals' => [
                                __('Total').'/google' => [],
                                __('Total').'/yandex' => [],
                            ],
                            'total' => [
                                __('Total') => [],
                            ],
                        ];

                        $dataChannelBanners = &$data['groups'][$channel->name]['banners'];
                        $dataChannelRows = &$data['groups'][$channel->name]['rows'];

                        if ($channel->yandex_counter) {

                            $headers = [];

                            $channel->yandex_token && $headers['Authorization'] = 'OAuth '.$channel->yandex_token;

                            //region ---- Запрос привязки объявлений Яндекс.Директ к метке utm_campaign ----

                            $response = self::requestYandexMetrika(
                                $channel->go_proxy_url,
                                [
                                    'ids' => $channel->yandex_counter,
                                    'date1' => $dataFromFormatted,
                                    'date2' => $dataToFormatted,
                                    'metrics' => 'ym:s:visits',
                                    'dimensions' => 'ym:s:lastDirectClickBanner,ym:s:UTMCampaign',
                                    'group' => 'all',
                                    'quantile' => 100,
                                    'limit' => 100000,
                                    'accuracy' => 'full',
                                ],
                                $headers
                            );

                            $response = self::parseResponseYandexMetrikaOrFail($response);

                            $response && $response->each(
                                function (\stdClass $group) use (&$dataChannelBanners) {

                                    $dimensions = &$group->dimensions;

                                    if (!is_null($dimensions['ym:s:UTMCampaign']->name)
                                        && !is_null($dimensions['ym:s:lastDirectClickBanner']->direct_id)
                                        && !isset($dataChannelBanners[$dimensions['ym:s:lastDirectClickBanner']->direct_id])
                                    ) {
                                        $dataChannelBanners[$dimensions['ym:s:lastDirectClickBanner']->direct_id] = $dimensions['ym:s:UTMCampaign']->name;
                                    }

                                }
                            );

                            $response && $response = $response->groupBy(
                                function (\stdClass $item) {
                                    return $item->dimensions['ym:s:lastDirectClickBanner']->direct_id;
                                }
                            );

                            unset($response);

                            //endregion

                            //region ---- Запрос всех меток зарегистрированных Яндекс.Метрикой за период с показателем отказов ----

                            $response = self::requestYandexMetrika(
                                $channel->go_proxy_url,
                                [
                                    'ids' => $channel->yandex_counter,
                                    'date1' => $dataFromFormatted,
                                    'date2' => $dataToFormatted,
                                    'metrics' => 'ym:s:visits',
                                    'dimensions' => 'ym:s:UTMCampaign,ym:s:UTMSource,ym:s:bounce',
                                    'group' => 'all',
                                    'quantile' => 100,
                                    'limit' => 100000,
                                    'accuracy' => 'full',
                                ],
                                $headers,
                                'stat',
                                $additionalYandexFilters
                            );

                            $response = self::parseResponseYandexMetrikaOrFail($response);

                            $response && $response->each(
                                function (\stdClass $group) use (
                                    $channel,
                                    &$data,
                                    &$dataChannelRows
                                ) {

                                    $groupRow = &$dataChannelRows[$group->dimensions['ym:s:UTMCampaign']->name.'/'.$group->dimensions['ym:s:UTMSource']->name];
                                    $dataChannelTotals = &$data['groups'][$channel->name]['totals'][__(
                                        'Total'
                                    ).'/'.$group->dimensions['ym:s:UTMSource']->name];
                                    $dataChannelTotal = &$data['groups'][$channel->name]['total'][__('Total')];
                                    $dataTotal = &$data['total_rows'][__('Total for all')];

                                    !isset($groupRow['visits']) && $groupRow['visits'] = 0;
                                    !isset($dataChannelTotals['visits']) && $dataChannelTotals['visits'] = 0;
                                    !isset($dataChannelTotal['visits']) && $dataChannelTotal['visits'] = 0;

                                    !isset($groupRow['bounce_visits']) && $groupRow['bounce_visits'] = 0;
                                    !isset($dataChannelTotals['bounce_visits']) && $dataChannelTotals['bounce_visits'] = 0;
                                    !isset($dataChannelTotal['bounce_visits']) && $dataChannelTotal['bounce_visits'] = 0;

                                    !isset($dataTotal['visits']) && $dataTotal['visits'] = 0;
                                    !isset($dataTotal['bounce_visits']) && $dataTotal['bounce_visits'] = 0;

                                    $groupRow['visits'] += (int)$group->metrics['ym:s:visits'];
                                    $dataChannelTotals['visits'] += (int)$group->metrics['ym:s:visits'];
                                    $dataChannelTotal['visits'] += (int)$group->metrics['ym:s:visits'];
                                    $dataTotal['visits'] += (int)$group->metrics['ym:s:visits'];

                                    if ($group->dimensions['ym:s:bounce']->id == 'yes') {
                                        $groupRow['bounce_visits'] += (int)$group->metrics['ym:s:visits'];
                                        $dataChannelTotals['bounce_visits'] += (int)$group->metrics['ym:s:visits'];
                                        $dataChannelTotal['bounce_visits'] += (int)$group->metrics['ym:s:visits'];
                                        $dataTotal['bounce_visits'] += (int)$group->metrics['ym:s:visits'];
                                    }

                                }
                            );

                            unset($response);

                            //endregion

                            //region ---- Получение доступных логинов Яндекс.Директ ----

                            $response = self::requestYandexMetrika(
                                $channel->go_proxy_url,
                                [
                                    'counters' => $channel->yandex_counter,
                                ],
                                $headers,
                                'management'
                            );

                            $clients = self::parseResponseYandexMetrikaOrFail($response, 'management');

                            unset ($response);

                            //endregion

                            //region ---- Получение стоимости кликов Яндекс.Директ в Долларах с переводом в Рубли ----

                            $clients && $clients->each(
                                function (\stdClass $client) use (
                                    $headers,
                                    $channel,
                                    $dataFromFormatted,
                                    $dataToFormatted,
                                    $usdRate,
                                    &$data,
                                    &$dataChannelBanners,
                                    &$dataChannelRows,
                                    $additionalYandexFilters
                                ) {


                                    $response = self::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'direct_client_logins' => $client->chief_login,
                                            'date1' => $dataFromFormatted,
                                            'date2' => $dataToFormatted,
                                            'metrics' => 'ym:ad:USDAdCost,ym:ad:clicks',
                                            'dimensions' => 'ym:ad:directBanner',
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 100000,
                                            'accuracy' => 'full',
                                            'currency' => 'USD'
                                        ],
                                        $headers,
                                        'stat',
                                        $additionalYandexFilters
                                    );

                                    $response = self::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (
                                            $channel,
                                            $usdRate,
                                            &$data,
                                            &$dataChannelBanners,
                                            &$dataChannelRows
                                        ) {
                                            if (count($dataChannelBanners) > 0) {
                                                $utm_campaign = ($dataChannelBanners[$group->dimensions['ym:ad:directBanner']->direct_id] ?? $group->dimensions['ym:ad:directBanner']->direct_id.''.$group->dimensions['ym:ad:directBanner']->name).'/yandex';
                                                $cost = round((float)$group->metrics['ym:ad:USDAdCost'] * $usdRate, 2);
                                                $groupRow = &$dataChannelRows[$utm_campaign];
                                                $dataChannelTotals = &$data['groups'][$channel->name]['totals'][__(
                                                    'Total'
                                                ).'/yandex'];
                                                $dataChannelTotal = &$data['groups'][$channel->name]['total'][__(
                                                    'Total'
                                                )];
                                                $dataTotal = &$data['total_rows'][__('Total for all')];

                                                !isset($groupRow['costs']) && $groupRow['costs'] = 0;
                                                !isset($dataChannelTotals['costs']) && $dataChannelTotals['costs'] = 0;
                                                !isset($dataChannelTotal['costs']) && $dataChannelTotal['costs'] = 0;

                                                !isset($groupRow['clicks']) && $groupRow['clicks'] = 0;
                                                !isset($dataChannelTotals['clicks']) && $dataChannelTotals['clicks'] = 0;
                                                !isset($dataChannelTotal['clicks']) && $dataChannelTotal['clicks'] = 0;

                                                !isset($dataTotal['costs']) && $dataTotal['costs'] = 0;
                                                !isset($dataTotal['clicks']) && $dataTotal['clicks'] = 0;

                                                $groupRow['costs'] += $cost;
                                                $dataChannelTotals['costs'] += $cost;
                                                $dataChannelTotal['costs'] += $cost;

                                                $groupRow['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                                $dataChannelTotals['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                                $dataChannelTotal['clicks'] += (int)$group->metrics['ym:ad:clicks'];

                                                $dataTotal['costs'] += $cost;
                                                $dataTotal['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                            }

                                        }
                                    );

                                    unset($response);

                                }
                            );

                            //endregion

                            //region ---- Если стоимость в долларах не получена - Получение стоимости кликов Яндекс.Директ в Рублях ----

                            $dataChannelYandexTotals = &$data['groups'][$channel->name]['totals'][__(
                                'Total'
                            ).'/yandex'];

                            (!isset($dataChannelYandexTotals['costs']) || !$dataChannelYandexTotals['costs'])
                            && $clients && $clients->each(
                                function (\stdClass $client) use (
                                    $headers,
                                    $channel,
                                    $dataFromFormatted,
                                    $dataToFormatted,
                                    &$data,
                                    &$dataChannelBanners,
                                    &$dataChannelRows,
                                    $additionalYandexFilters
                                ) {


                                    $response = self::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'direct_client_logins' => $client->chief_login,
                                            'date1' => $dataFromFormatted,
                                            'date2' => $dataToFormatted,
                                            'metrics' => 'ym:ad:RUBAdCost,ym:ad:clicks',
                                            'dimensions' => 'ym:ad:directBanner',
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 100000,
                                            'accuracy' => 'full',
                                            'currency' => 'RUB'
                                        ],
                                        $headers,
                                        'stat',
                                        $additionalYandexFilters
                                    );

                                    $response = self::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (
                                            $channel,
                                            &$data,
                                            &$dataChannelBanners,
                                            &$dataChannelRows
                                        ) {

                                            if (isset($dataChannelBanners[$group->dimensions['ym:ad:directBanner']->direct_id])) {

                                                $utm_campaign = $dataChannelBanners[$group->dimensions['ym:ad:directBanner']->direct_id].'/yandex';
                                                $cost = round((float)$group->metrics['ym:ad:RUBAdCost'], 2);
                                                $groupRow = &$dataChannelRows[$utm_campaign];
                                                $dataChannelTotals = &$data['groups'][$channel->name]['totals'][__(
                                                    'Total'
                                                ).'/yandex'];
                                                $dataChannelTotal = &$data['groups'][$channel->name]['total'][__(
                                                    'Total'
                                                )];
                                                $dataTotal = &$data['total_rows'][__('Total for all')];

                                                !isset($groupRow['costs']) && $groupRow['costs'] = 0;
                                                !isset($dataChannelTotals['costs']) && $dataChannelTotals['costs'] = 0;
                                                !isset($dataChannelTotal['costs']) && $dataChannelTotal['costs'] = 0;

                                                !isset($groupRow['clicks']) && $groupRow['clicks'] = 0;
                                                !isset($dataChannelTotals['clicks']) && $dataChannelTotals['clicks'] = 0;
                                                !isset($dataChannelTotal['clicks']) && $dataChannelTotal['clicks'] = 0;

                                                !isset($dataTotal['costs']) && $dataTotal['costs'] = 0;
                                                !isset($dataTotal['clicks']) && $dataTotal['clicks'] = 0;

                                                $groupRow['costs'] += $cost;
                                                $dataChannelTotals['costs'] += $cost;
                                                $dataChannelTotal['costs'] += $cost;

                                                $groupRow['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                                $dataChannelTotals['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                                $dataChannelTotal['clicks'] += (int)$group->metrics['ym:ad:clicks'];

                                                $dataTotal['costs'] += $cost;
                                                $dataTotal['clicks'] += (int)$group->metrics['ym:ad:clicks'];
                                            }
                                        }
                                    );

                                    unset($response);

                                }
                            );

                            //endregion


                            if (
                                $channel->google_counter && \Storage::exists(
                                    'keys/google/'.strtolower($channel->name).'.json'
                                )
                                && !$additionalGoogleFilters['is_error_filter']
                            ) {
                                // Create and configure a new client object.
                                $client = new \Google_Client();
                                $client->setApplicationName($channel->name);
                                $client->setAuthConfig(
                                    \Storage::path('keys/google/'.strtolower($channel->name).'.json')
                                );
                                $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
                                $client->setDefer(true);
                                $analytics = new \Google_Service_AnalyticsReporting($client);

                                // Create the DateRange object.
                                $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
                                $dateRange->setStartDate($dataFromFormatted);
                                $dateRange->setEndDate($dataToFormatted);

                                $dimensions = new \Google_Service_AnalyticsReporting_Dimension();
                                $dimensions->setName('ga:campaign');

                                // Create the Metrics object.
                                $costs = new \Google_Service_AnalyticsReporting_Metric();
                                $costs->setExpression("ga:adCost");
                                $costs->setAlias("cost");

                                // Create the Metrics object.
                                $clicks = new \Google_Service_AnalyticsReporting_Metric();
                                $clicks->setExpression("ga:adClicks");
                                $clicks->setAlias("clicks");

                                // Create the ReportRequest object.
                                $request = new \Google_Service_AnalyticsReporting_ReportRequest();
                                $request->setViewId($channel->google_counter);
                                $request->setDateRanges($dateRange);
                                $request->setMetrics([$costs, $clicks]);
                                $request->setDimensions($dimensions);


                                if (count($additionalGoogleFilters['filters'])) {

                                    $dimensionFilterClause = new \Google_Service_AnalyticsReporting_DimensionFilterClause(
                                    );


                                    foreach ($additionalGoogleFilters['filters'] as $goFilter) {
                                        $dimensionFilter = new \Google_Service_AnalyticsReporting_DimensionFilter();
                                        $dimensionFilter->setDimensionName($goFilter['name']);
                                        $dimensionFilter->setOperator($goFilter['expression']);
                                        $dimensionFilter->setExpressions($goFilter['values']);
                                        $dimensionFilterClause->setFilters($dimensionFilter);
                                    }

                                    $request->setDimensionFilterClauses($dimensionFilterClause);
                                }

                                $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
                                $body->setReportRequests([$request]);
                                /**
                                 * @var $google_request \GuzzleHttp\Psr7\Request
                                 */
                                $google_request = $analytics->reports->batchGet($body);
                                $expected_class = $google_request->getHeaderLine('X-Php-Expected-Class');
                                $google_request = $google_request->withoutHeader('X-Php-Expected-Class');
                                $google_request = $google_request->withHeader('Go', (string)$google_request->getUri());
                                $google_request = $google_request->withUri((new Uri($channel->go_proxy_url)));
                                $response = $client->execute($google_request, $expected_class);

                                foreach ($response->getReports() as $report) {
                                    /**
                                     * @var $report \Google_Service_AnalyticsReporting_Report
                                     */
                                    foreach ($report->getData()->getRows() as $row) {
                                        /**
                                         * @var $row \Google_Service_AnalyticsReporting_ReportRow
                                         */
                                        $utm_campaign = $row->getDimensions()[0].'/google';
                                        /**
                                         * @var $metric \Google_Service_AnalyticsReporting_DateRangeValues
                                         */
                                        $metric = $row->getMetrics()[0];
                                        $cost = round((float)$metric->getValues()[0] * 1.2 * $usdRate, 2);
                                        $data['groups'][$channel->name]['rows'][$utm_campaign]['costs'] = isset($data['groups'][$channel->name]['rows'][$utm_campaign]['costs']) ? (float)$data['groups'][$channel->name]['rows'][$utm_campaign]['costs'] + $cost : $cost;
                                        $data['groups'][$channel->name]['totals'][__(
                                            'Total'
                                        ).'/google']['costs'] = isset(
                                            $data['groups'][$channel->name]['totals'][__(
                                                'Total'
                                            ).'/google']['costs']
                                        ) ? (float)$data['groups'][$channel->name]['totals'][__(
                                                'Total'
                                            ).'/google']['costs'] + $cost : $cost;
                                        $data['groups'][$channel->name]['total'][__(
                                            'Total'
                                        )]['costs'] = isset(
                                            $data['groups'][$channel->name]['total'][__(
                                                'Total'
                                            )]['costs']
                                        ) ? (float)$data['groups'][$channel->name]['total'][__(
                                                'Total'
                                            )]['costs'] + $cost : $cost;

                                        $data['total_rows'][__(
                                            'Total for all'
                                        )]['costs'] = isset(
                                            $data['total_rows'][__(
                                                'Total for all'
                                            )]['costs']
                                        ) ? (float)$data['total_rows'][__('Total for all')]['costs'] + $cost : $cost;

                                        $clicks_per_utm = $metric->getValues()[1];
                                        $data['groups'][$channel->name]['rows'][$utm_campaign]['clicks'] = isset($data['groups'][$channel->name]['rows'][$utm_campaign]['clicks']) ? (float)$data['groups'][$channel->name]['rows'][$utm_campaign]['clicks'] + $clicks_per_utm : $clicks_per_utm;
                                        $data['groups'][$channel->name]['totals'][__(
                                            'Total'
                                        ).'/google']['clicks'] = isset(
                                            $data['groups'][$channel->name]['totals'][__(
                                                'Total'
                                            ).'/google']['clicks']
                                        ) ? (float)$data['groups'][$channel->name]['totals'][__(
                                                'Total'
                                            ).'/google']['clicks'] + $clicks_per_utm : $clicks_per_utm;
                                        $data['groups'][$channel->name]['total'][__(
                                            'Total'
                                        )]['clicks'] = isset(
                                            $data['groups'][$channel->name]['total'][__(
                                                'Total'
                                            )]['clicks']
                                        ) ? (float)$data['groups'][$channel->name]['total'][__(
                                                'Total'
                                            )]['clicks'] + $clicks_per_utm : $clicks_per_utm;

                                        $data['total_rows'][__(
                                            'Total for all'
                                        )]['clicks'] = isset(
                                            $data['total_rows'][__(
                                                'Total for all'
                                            )]['clicks']
                                        ) ? (float)$data['total_rows'][__(
                                                'Total for all'
                                            )]['clicks'] + $clicks_per_utm : $clicks_per_utm;

                                    }
                                }
                            }
                        }

                    }
                );

                $configuration = Configuration::all()->where(
                    'name',
                    'settings_order_detail_states_for_expenses'
                )->first();
                $values = $configuration ? json_decode($configuration->values) : [];
                $successful_states = $values->successful_states ?? [];
                $minimal_states = is_object($values) && isset($values->minimal_states) ? $values->minimal_states : [];


                //endregion
                $orders->each(
                    function (Order $order) use (&$data, $successful_states, $minimal_states) {
                        $orderDetails = $order->orderDetails->filter(
                            function (OrderDetail $orderDetail) use ($successful_states) {
                                return in_array($orderDetail->currentState()->id, ($successful_states ?? []));
                            }
                        );
                        if ($orderDetails->count() < 1) {
                            $orderDetails = $order->orderDetails->filter(
                                function (OrderDetail $orderDetail) use ($minimal_states
                                ) {
                                    return is_array($minimal_states) ? in_array(
                                            $orderDetail->currentState()->id,
                                            $minimal_states
                                        ) && $orderDetail->product->need_guarantee : false;
                                }
                            )->sortBy('price')->slice(0, 1);
                        }
                        if ($orderDetails->count() < 1) {
                            $orderDetails = $order->orderDetails->filter(
                                function (OrderDetail $orderDetail) use ($minimal_states
                                ) {
                                    return is_array($minimal_states) ? in_array(
                                        $orderDetail->currentState()->id,
                                        $minimal_states
                                    ) : false;
                                }
                            )->sortBy('price')->slice(0, 1);
                        }

                        $utm_campaign = $order->utm_campaign ? $order->utm_campaign.'/'.$order->utm_source : ($order->search_query ? 'search_query/'.$order->search_query : 'utm no');
                        $utm_source = $order->utm_campaign ? '/'.$order->utm_source : ($order->search_query ? '/'.$order->search_query : '/utm no');
                        $data['groups'][$order->channel->name]['rows'][$utm_campaign]['orders'][$order->id] = 1;
                        $data['groups'][$order->channel->name]['totals'][__(
                            'Total'
                        ).$utm_source]['orders'][$order->id] = 1;
                        $data['groups'][$order->channel->name]['total'][__('Total')]['orders'][$order->id] = 1;
                        $data['total_rows'][__('Total for all')]['orders'][$order->id] = 1;

                        if ($orderDetails->count() > 0) {
                            $data['groups'][$order->channel->name]['rows'][$utm_campaign]['successful_orders'][$order->id] = 1;
                            $data['groups'][$order->channel->name]['totals'][__(
                                'Total'
                            ).$utm_source]['successful_orders'][$order->id] = 1;
                            $data['groups'][$order->channel->name]['total'][__(
                                'Total'
                            )]['successful_orders'][$order->id] = 1;
                            $data['total_rows'][__('Total for all')]['successful_orders'][$order->id] = 1;
                        }

                        /**
                         * @var \App\OrderDetail $orderDetail
                         */
                        foreach ($orderDetails as $orderDetail) {

                            $data['groups'][$order->channel->name]['rows'][$utm_campaign]['price'] = (float)(isset($data['groups'][$order->channel->name]['rows'][$utm_campaign]['price']) ? $data['groups'][$order->channel->name]['rows'][$utm_campaign]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['groups'][$order->channel->name]['totals'][__(
                                'Total'
                            ).$utm_source]['price'] = (float)(isset(
                                $data['groups'][$order->channel->name]['totals'][__(
                                    'Total'
                                ).$utm_source]['price']
                            ) ? $data['groups'][$order->channel->name]['totals'][__(
                                    'Total'
                                ).$utm_source]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['groups'][$order->channel->name]['total'][__(
                                'Total'
                            )]['price'] = (float)(isset(
                                $data['groups'][$order->channel->name]['total'][__(
                                    'Total'
                                )]['price']
                            ) ? $data['groups'][$order->channel->name]['total'][__(
                                    'Total'
                                )]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                            $data['total_rows'][__('Total for all')]['price'] = (float)(isset(
                                $data['total_rows'][__(
                                    'Total for all'
                                )]['price']
                            ) ? $data['total_rows'][__(
                                    'Total for all'
                                )]['price'] + $orderDetail->price * $orderDetail->currency->currency_rate : $orderDetail->price * $orderDetail->currency->currency_rate);

                        }

                        ksort($data['groups'][$order->channel->name]['rows']);

                    }
                );
            }
            if ($request->save) {
                Configuration::updateOrCreate(
                    ['name' => 'reportAdsByChannels_user_'.\Auth::user()->getAuthIdentifier()],
                    [
                        'values' => json_encode(
                            [
                                'dateFrom' => is_null($request->from) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->from)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'dateTo' => is_null($request->to) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->to)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'device' => $request->device,
                                'age' => $request->age,
                                'gender' => $request->gender,
                                'indicator_min_conversions' => $request->indicator_min_conversions,
                                'indicator_clicks_delta' => $request->indicator_clicks_delta,
                                'show_utm' => $request->show_utm ? $request->show_utm : 0,
                            ]
                        ),
                    ]
                );
            }

            $carriers = $request->carriers;
            $dateFrom = $request->from;
            $dateTo = $request->to;
            $device = $request->device;
            $age = $request->age;
            $gender = $request->gender;
            $indicator_min_conversions = $request->indicator_min_conversions;
            $indicator_clicks_delta = $request->indicator_clicks_delta;
        } else {
            $configuration = Configuration::all()->where(
                'name',
                'reportAdsByChannels_user_'.\Auth::user()->getAuthIdentifier()
            )->first();
            $values = $configuration ? json_decode($configuration->values) : [];
            $carriers = is_object($values) && isset($values->carriers) ? $values->carriers : [];
            $dateFrom = is_object($values) && isset($values->dateFrom) && !is_null($values->dateFrom) ? Carbon::now(
            )->addDays($values->dateFrom)->format('d-m-Y') : null;
            $dateTo = is_object($values) && isset($values->dateTo) && !is_null($values->dateTo) ? Carbon::now(
            )->addDays($values->dateTo)->format('d-m-Y') : null;
            $device = is_object($values) && isset($values->device) ? $values->device : null;
            $age = is_object($values) && isset($values->age) ? $values->age : null;
            $gender = is_object($values) && isset($values->gender) ? $values->gender : null;
            $indicator_min_conversions = is_object(
                $values
            ) && isset($values->indicator_min_conversions) ? $values->indicator_min_conversions : null;
            $indicator_clicks_delta = is_object(
                $values
            ) && isset($values->indicator_clicks_delta) ? $values->indicator_clicks_delta : null;
            $utm = isset($values->show_utm) ? 1 : 0;
        }

        $devices = [
            0 => 'Все',
            'mobile' => 'Мобильные',
            'tablet' => 'Планшеты',
            'desktop' => 'Десктопы',
        ];
        $ages = [
            0 => 'Все',
            '17' => 'Младше 18',
            '18' => 'От 18 до 24',
            '25' => 'От 25 до 34',
            '35' => 'От 35 до 44',
            '45' => 'От 45 до 54',
            '55' => '55 и старше',
        ];
        $genders = [
            0 => 'Все',
            'female' => 'Женщины',
            'male' => 'Мужчины',
        ];
        $orderDetailStates = OrderDetailState::pluck('name', 'id');

        $dataUtmGroups = [];

        foreach ($data['groups'] as $dataGroupName => &$dataGroup) {
            $dataGroup['rows'] = $dataGroup['rows'] ?? [];

            foreach ($dataGroup['rows'] as $rowName => &$dataRow) {

                $utms = explode('/', $rowName);
                $utmCampaign = $utms[0] ?? '###';
                $utmSource = $utms[1] ?? '';

                $sqlDates = [];

                $dataFromCarbon = $dataFromCarbon ?? null;
                $dataToCarbon = $dataToCarbon ?? null;

                if (!is_null($dataFromCarbon)) {
                    $sqlDates[] = "Date >= '{$dataFromCarbon->format('Y-m-d')}'";
                }

                if (!is_null($dataToCarbon)) {
                    $sqlDates[] = "Date <= '{$dataToCarbon->format('Y-m-d')}'";
                }

                $uniqueClientIDQuantity = isset($data['yandex_counters'][$dataGroupName]) ?
                    $this
                        ->getClientsQuantityFromMetrikaLogs(
                            $data['yandex_counters'][$dataGroupName],
                            array_merge(
                                [
                                    'ClientID > 0',
                                    "UTMCampaign = '{$utmCampaign}'",
                                    "UTMSource = '{$utmSource}'",
                                ],
                                $sqlDates
                            )
                        ) : 0;

                $zeroClientIDQuantity = isset($data['yandex_counters'][$dataGroupName]) ?
                    $this
                        ->getClientsQuantityFromMetrikaLogs(
                            $data['yandex_counters'][$dataGroupName],
                            array_merge(
                                [
                                    'ClientID = 0',
                                    "UTMCampaign = '{$utmCampaign}'",
                                    "UTMSource = '{$utmSource}'",
                                ],
                                $sqlDates
                            ),
                            false
                        ) : 0;

                $dataRow['unique_client_id'] = $uniqueClientIDQuantity;
                $dataRow['zero_client_id'] = $zeroClientIDQuantity;

                if (isset(
                    $data['groups'][$dataGroupName]['totals'][__(
                        'Total'
                    ).'/'.$utmSource]
                )) {
                    $data['groups'][$dataGroupName]['totals'][__(
                        'Total'
                    ).'/'.$utmSource]['unique_client_id'] = ($data['groups'][$dataGroupName]['totals'][__(
                                'Total'
                            ).'/'.$utmSource]['unique_client_id'] ?? 0) + $uniqueClientIDQuantity;
                    $data['groups'][$dataGroupName]['totals'][__(
                        'Total'
                    ).'/'.$utmSource]['zero_client_id'] = ($data['groups'][$dataGroupName]['totals'][__(
                                'Total'
                            ).'/'.$utmSource]['zero_client_id'] ?? 0) + $zeroClientIDQuantity;
                }

                if (isset($data['total_rows'][__('Total for all')])) {
                    $data['total_rows'][__('Total for all')]['unique_client_id'] = ($data['total_rows'][__(
                                'Total for all'
                            )]['unique_client_id'] ?? 0) + $uniqueClientIDQuantity;
                    $data['total_rows'][__('Total for all')]['zero_client_id'] = ($data['total_rows'][__(
                                'Total for all'
                            )]['zero_client_id'] ?? 0) + $zeroClientIDQuantity;
                }

                $dataRow['utm_groups'] = UtmGroup::getGroupsForString("::{$dataGroupName}::{$rowName}");
                $dataRow['clicks'] = isset($dataRow['clicks']) ? $dataRow['clicks'] : 0;
                $dataRow['costs'] = isset($dataRow['costs']) ? $dataRow['costs'] : 0;
                $dataRow['visits'] = isset($dataRow['visits']) ? $dataRow['visits'] : 0;
                $dataRow['bounce_visits'] = isset($dataRow['bounce_visits']) ? $dataRow['bounce_visits'] : 0;
                $dataRow['orders'] = isset($dataRow['orders']) ? $dataRow['orders'] : [];
                $dataRow['successful_orders'] = isset($dataRow['successful_orders']) ? $dataRow['successful_orders'] : [];
                $dataRow['price'] = isset($dataRow['price']) ? $dataRow['price'] : 0;


                /**
                 * @var UtmGroup $utmGroup
                 */
                foreach ($dataRow['utm_groups'] as $utmGroup) {
                    if (!isset($dataUtmGroups[$utmGroup->name])) {
                        $dataUtmGroups[$utmGroup->name] = [
                            'clicks' => 0,
                            'costs' => 0,
                            'visits' => 0,
                            'bounce_visits' => 0,
                            'orders' => [],
                            'successful_orders' => [],
                            'price' => 0,
                            'utmGroup' => $utmGroup,
                            'unique_client_id' => 0,
                            'zero_client_id' => 0,
                        ];
                    }

                    $dataUtmGroups[$utmGroup->name] = [
                        'clicks' => $dataUtmGroups[$utmGroup->name]['clicks'] + $dataRow['clicks'],
                        'costs' => $dataUtmGroups[$utmGroup->name]['costs'] + $dataRow['costs'],
                        'visits' => $dataUtmGroups[$utmGroup->name]['visits'] + $dataRow['visits'],
                        'bounce_visits' => $dataUtmGroups[$utmGroup->name]['bounce_visits'] + $dataRow['bounce_visits'],
                        'orders' => array_merge($dataUtmGroups[$utmGroup->name]['orders'], $dataRow['orders']),
                        'successful_orders' => array_merge(
                            $dataUtmGroups[$utmGroup->name]['successful_orders'],
                            $dataRow['successful_orders']
                        ),
                        'price' => $dataUtmGroups[$utmGroup->name]['price'] + $dataRow['price'],
                        'utmGroup' => $dataUtmGroups[$utmGroup->name]['utmGroup'],
                        'unique_client_id' => $dataUtmGroups[$utmGroup->name]['unique_client_id'] + $uniqueClientIDQuantity,
                        'zero_client_id' => $dataUtmGroups[$utmGroup->name]['zero_client_id'] + $zeroClientIDQuantity,
                    ];
                }
            }
        }

        $dataFromCarbon = $dateFrom ?
            Carbon::createFromFormat(
                'd-m-Y',
                $dateFrom
            )
                ->setTime(0, 0)
            : null;

        $dataToCarbon = $dateTo ?
            Carbon::createFromFormat(
                'd-m-Y',
                $dateTo
            )
                ->setTime(23, 59, 59)
            : null;

        $reportDays = !is_null($dataFromCarbon) && !is_null($dataToCarbon) ? $dataToCarbon->diffInDays(
                $dataFromCarbon
            ) + 1 : 0;

        $dataUtmGroups = Arr::sort(
            $dataUtmGroups,
            function ($row) {
                return $row['utmGroup']->sort_order;
            }
        );

        //Флаг отображения utm групп
        //Если не достали по умолчанию
        if(!isset($utm)) {
            $utm = isset($request->show_utm) ? $request->show_utm : 0;
        }

        return view(
            'analytics.reports.ads.by.channels',
            compact(
                'orderDetailStates',
                'data',
                'successful_states',
                'minimal_states',
                'carriers',
                'dateFrom',
                'dateTo',
                'device',
                'devices',
                'age',
                'ages',
                'gender',
                'genders',
                'indicator_min_conversions',
                'indicator_clicks_delta',
                'dataUtmGroups',
                'reportDays',
                'utm'
            )
        );
    }

    /**
     * Рекламные графики по каналам
     *
     * и нужно сравнение периодов, то есть для двух периодов я хочу видеть по каждой группе меток (и где-нибудь отдельно еще и по каждой метке) всё те же самые значения, что и сейчас в отчете, но только для каждого значения еще и такое же значение, насколько оно изменилось. Типа клики в периоде 1, клики в периоде 2, изменение в процентах (+-x%)
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reportAdsGraphByChannels(Request $request)
    {
        if ($request->report) {
            if ($request->save) {
                Configuration::updateOrCreate(
                    ['name' => 'reportAdsGraphByChannels_user_'.\Auth::user()->getAuthIdentifier()],
                    [
                        'values' => json_encode(
                            [
                                'dateFrom' => is_null($request->from) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->from)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'dateTo' => is_null($request->to) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->to)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'successful_states' => $request->successful_states,
                                'minimal_states' => $request->minimal_states,
                                'utm_campaigns' => $request->utm_campaigns,
                                'utm_sources' => $request->utm_sources,
                                'utm_groups' => $request->utm_groups,
                                'devices' => $request->devices,
                                'ages' => $request->ages,
                                'genders' => $request->genders,
                                'chart_selected' => $request->chart_selected,
                                'n_average' => $request->n_average,
                                'multiChartFirst' => $request->multiChartFirst,
                                'multiChartSecond' => $request->multiChartSecond,
                            ]
                        ),
                    ]
                );
            }
            $dateFrom = $request->from ?? null;
            $dateTo = $request->to ?? null;
            $successful_states = $request->successful_states ?? [];
            $minimal_states = $request->minimal_states ?? [];
            $utm_campaigns = $request->utm_campaigns ?? [];
            $utm_sources = $request->utm_sources ?? [];
            $utm_groups = $request->utm_groups ?? [];
            $devices = $request->devices ?? [];
            $ages = $request->ages ?? [];
            $genders = $request->genders ?? [];
            $chart_selected = $request->chart_selected ?? null;
            $n_average = $request->n_average ?? 0;
            $multiChartFirst = $request->multiChartFirst ?? 0;
            $multiChartSecond = $request->multiChartSecond ?? 0;
        } else {
            $configuration = Configuration::all()->where(
                'name',
                'reportAdsGraphByChannels_user_'.\Auth::user()->getAuthIdentifier()
            )->first();
            $values = $configuration ? json_decode($configuration->values) : new \stdClass();
            $dateFrom = isset($values->dateFrom) && !is_null($values->dateFrom) ? Carbon::now()->addDays(
                $values->dateFrom
            )->format('d-m-Y') : null;
            $dateTo = isset($values->dateTo) && !is_null($values->dateTo) ? Carbon::now()->addDays(
                $values->dateTo
            )->format('d-m-Y') : null;
            $successful_states = $values->successful_states ?? [];
            $minimal_states = $values->minimal_states ?? [];
            $utm_campaigns = $values->utm_campaigns ?? [];
            $utm_sources = $values->utm_sources ?? [];
            $utm_groups = $values->utm_groups ?? [];
            $devices = $values->devices ?? [];
            $ages = $values->ages ?? [];
            $genders = $values->genders ?? [];
            $chart_selected = $values->chart_selected ?? null;
            $n_average = $values->n_average ?? 0;
            $multiChartFirst = $values->multiChartFirst ?? 0;
            $multiChartSecond = $values->multiChartSecond ?? 0;
        }

        /**
         * Массив настроек БД
         * @var array $dbConfig
         */
        $dbConfig = [
            'host' => config('clickhouse.host'),
            'port' => config('clickhouse.port'),
            'username' => config('clickhouse.username'),
            'password' => config('clickhouse.password'),
        ];

        /**
         * Клиент БД
         * @var Client $db
         */
        $db = new Client($dbConfig);
        $db->settings()->max_execution_time(200);

        $devicesGroups = [
            'mobile' => __('Mobile'),
            'tablet' => __('Tablet'),
            'desktop' => __('Desktop'),
        ];
        $agesGroups = [
            '17' => __('Under 18'),
            '18' => __('from 18 to 24'),
            '25' => __('from 25 to 34'),
            '35' => __('from 35 to 44'),
            '45' => __('from 45 to 54'),
            '55' => __('55 and older'),
        ];
        $gendersGroups = [
            'female' => __('Females'),
            'male' => __('Males'),
        ];

        /**
         * Массив конечной информации для графиков
         * @var array $graphs
         */
        $graphs = [];

        /**
         * Массив шаблонов цветов
         * @var array $colors
         */
        $colors = [
            "#FF0000",
            "#00FF00",
            "#0000FF",
            "#FFFF00",
            "#FF00FF",
            "#00FFFF",
            "#000000",
            "#800000",
            "#008000",
            "#000080",
            "#808000",
            "#800080",
            "#008080",
            "#808080",
            "#C00000",
            "#00C000",
            "#0000C0",
            "#C0C000",
            "#C000C0",
            "#00C0C0",
            "#C0C0C0",
            "#400000",
            "#004000",
            "#000040",
            "#404000",
            "#400040",
            "#004040",
            "#404040",
            "#200000",
            "#002000",
            "#000020",
            "#202000",
            "#200020",
            "#002020",
            "#202020",
            "#600000",
            "#006000",
            "#000060",
            "#606000",
            "#600060",
            "#006060",
            "#606060",
            "#A00000",
            "#00A000",
            "#0000A0",
            "#A0A000",
            "#A000A0",
            "#00A0A0",
            "#A0A0A0",
            "#E00000",
            "#00E000",
            "#0000E0",
            "#E0E000",
            "#E000E0",
            "#00E0E0",
            "#E0E0E0",
        ];

        //region получение всех заказов за период с учетом фильтров

        $ordersAll = Order::where('is_hidden', 0);

        if (!is_null($dateFrom)) {
            $ordersAll = $ordersAll->where(
                'created_at',
                '>=',
                Carbon::createFromFormat('d-m-Y', $dateFrom)->setTime(0, 0, 0, 0)->toDateTimeString()
            );
        }
        if (!is_null($dateTo)) {
            $ordersAll = $ordersAll->where(
                'created_at',
                '<=',
                Carbon::createFromFormat('d-m-Y', $dateTo)->setTime(23, 59, 59, 999)->toDateTimeString()
            );
        }

        $ordersAll = $ordersAll->orderBy('created_at')->get();

        if (!empty($utm_campaigns)) {
            $ordersAll = $ordersAll->filter(
                function (Order $order) use ($utm_campaigns) {
                    if (!is_object($order->utm) || $order->utm->campaign == '') {
                        return in_array(__('-- No UTM --'), $utm_campaigns);
                    }

                    if (in_array($order->utm->campaign, $utm_campaigns)) {
                        return true;
                    }

                    return in_array(__('-- Error UTM --'), $utm_campaigns)
                        && preg_match('/[\{\}&\?\=]+/', $order->utm->campaign);
                }
            );
        }

        if (!empty($utm_sources)) {
            $ordersAll = $ordersAll->filter(
                function (Order $order) use ($utm_sources) {
                    if (!is_object($order->utm) || $order->utm->source == '') {
                        return in_array(__('-- No UTM --'), $utm_sources);
                    }

                    if (in_array($order->utm->source, $utm_sources)) {
                        return true;
                    }

                    return in_array(__('-- Error UTM --'), $utm_sources)
                        && preg_match('/[\{\}&\?\=]+/', $order->utm->source);
                }
            );
        }

        if (!empty($utm_groups)) {
            $ordersAll = $ordersAll->filter(
                function (Order $order) use ($utm_groups) {

                    $utmString = ($order->utm ?? '') == '' || $order->utm->campaign == '' ? 'utm no' : "{$order->utm->campaign}/{$order->utm->source}";

                    $testString = "::{$order->channel->name}::{$utmString}";

                    /**
                     * @var UtmGroup $UTMGroup
                     */

                    foreach (UtmGroup::getGroupsForString($testString) as $UTMGroup) {
                        if (in_array($UTMGroup->id, $utm_groups)) {
                            return true;
                        }
                    }

                    return false;
                }
            );
        }

        if (!empty($devices)) {
            $ordersAll = $ordersAll->filter(
                function (Order $order) use ($devices) {
                    return in_array($order->device, $devices);
                }
            );
        }

        if (!empty($ages)) {
            $ordersAll = $ordersAll->filter(
                function (Order $order) use ($ages) {
                    return in_array($order->age, $ages);
                }
            );
        }

        if (!empty($genders)) {
            $ordersAll = $ordersAll->filter(
                function (Order $order) use ($genders) {
                    return in_array($order->gender, $genders);
                }
            );
        }

        /**
         * Все заказы за период с учетом фильтров
         * @var Collection $ordersAll
         */

        $ordersAll = $ordersAll->reduce(
            function (Collection $acc, Order $order) {
                return $acc->put(
                    $order->id,
                    collect(
                        [
                            'order' => $order,
                            'orderDetails' => $order->orderDetails,
                            'sum' => $order->orderDetails->sum(
                                function (OrderDetail $orderDetail) {
                                    return round($orderDetail->price * $orderDetail->currency->currency_rate, 2);
                                }
                            ),
                            'created_at' => $order->created_at->getTimestamp(),
                        ]
                    )
                );
            },
            collect()
        );

        //endregion

        //region получение успешных заказов за период с учетом фильтров

        /**
         * Успешные заказы за период с учетом фильтров
         * @var Collection $ordersSuccess
         */
        $ordersSuccess = $ordersAll->reduce(
            function (Collection $acc, Collection $item) use ($successful_states, $minimal_states) {

                /**
                 * @var Order $order
                 */
                $order = $item->get('order');

                /**
                 * @var Collection $itemOrderDetails
                 */
                $itemOrderDetails = $item->get('orderDetails');

                $orderDetails = $itemOrderDetails->filter(
                    function (OrderDetail $orderDetail) use ($successful_states) {
                        return in_array($orderDetail->currentState()->id, $successful_states);
                    }
                );

                if ($orderDetails->isEmpty()) {
                    $orderDetails = $itemOrderDetails->filter(
                        function (OrderDetail $orderDetail) use ($minimal_states) {
                            return in_array($orderDetail->currentState()->id, $minimal_states)
                                && $orderDetail->product->need_guarantee;
                        }
                    )->sortBy('price')->slice(0, 1);
                }

                if ($orderDetails->isEmpty()) {
                    $orderDetails = $itemOrderDetails->filter(
                        function (OrderDetail $orderDetail) use ($minimal_states) {
                            return in_array($orderDetail->currentState()->id, $minimal_states);
                        }
                    )->sortBy('price')->slice(0, 1);
                }

                if (!$orderDetails->isEmpty()) {
                    $acc->put(
                        $order->id,
                        collect(
                            [
                                'order' => $order,
                                'orderDetails' => $orderDetails,
                                'sum' => $orderDetails->sum(
                                    function (OrderDetail $orderDetail) {
                                        return round($orderDetail->price * $orderDetail->currency->currency_rate, 2);
                                    }
                                ),
                                'created_at' => $item->get('created_at'),
                            ]
                        )
                    );
                }

                return $acc;
            },
            collect()
        );

        //endregion

        //region построение шаблона всех дней периода
        /**
         * Шаблон всех дней периода
         * @var array
         */
        $periodTemplate = [];

        $dateFromForTemplate = is_null($dateFrom) ?
            Carbon::createFromTimestamp($ordersAll->min('created_at'))
            :
            Carbon::createFromFormat(
                'd-m-Y',
                $dateFrom
            );

        $timestampToForTemplate = (is_null($dateTo) ?
            Carbon::createFromTimestamp($ordersAll->max('created_at'))
            :
            Carbon::createFromFormat(
                'd-m-Y',
                $dateTo
            ))->setTime(23, 59, 59, 999)->getTimestamp();

        while ($dateFromForTemplate->getTimestamp() < $timestampToForTemplate) {
            $periodTemplate[$dateFromForTemplate->format('d-m')] = 0;
            $dateFromForTemplate = $dateFromForTemplate->addDay();
        }

        //endregion


        $dataSets = [
            'ordersQuantity' => [],
            'ordersSum' => [],
            'ordersQuantitySuccessByChannels' => [],
            'ordersSumSuccessByChannels' => [],
            'ordersQuantitySuccessBySources' => [],
            'ordersSumSuccessBySources' => [],
            'ordersQuantitySuccessByGroups' => [],
            'ordersSumSuccessByGroups' => [],
            'ordersQuantitySuccessByDevices' => [],
            'ordersSumSuccessByDevices' => [],
            'ordersQuantitySuccessByAges' => [],
            'ordersSumSuccessByAges' => [],
            'ordersQuantitySuccessByGenders' => [],
            'ordersSumSuccessByGenders' => [],
            'clicksQuantityByChannels' => [],
            'clicksSumByChannels' => [],
            'clicksQuantityBySources' => [],
            'clicksSumBySources' => [],
            'clicksQuantityByGroups' => [],
            'clicksSumByGroups' => [],
            'clicksQuantityByDevices' => [],
            'clicksSumByDevices' => [],
            'clicksQuantityByAges' => [],
            'clicksSumByAges' => [],
            'clicksQuantityByGenders' => [],
            'clicksSumByGenders' => [],
            'conversionsSumByChannels' => [],
            'conversionsSumBySources' => [],
            'conversionsSumByGroups' => [],
            'conversionsSumByDevices' => [],
            'conversionsSumByAges' => [],
            'conversionsSumByGenders' => [],
            'costsPercentByChannels' => [],
            'costsPercentBySources' => [],
            'costsPercentByGroups' => [],
            'costsPercentByDevices' => [],
            'costsPercentByAges' => [],
            'costsPercentByGenders' => [],
        ];

        $charts = [
            'ordersQuantity' => __('Orders, PCs.'),
            'ordersSum' => __('Orders, rub.'),
            'ordersQuantitySuccessByChannels' => __('Orders, PCs.')." - ".__('by Channels'),
            'ordersSumSuccessByChannels' => __('Orders, rub.')." - ".__('by Channels'),
            'ordersQuantitySuccessBySources' => __('Orders, PCs.')." - ".__('by UTM Sources'),
            'ordersSumSuccessBySources' => __('Orders, rub.')." - ".__('by UTM Sources'),
            'ordersQuantitySuccessByGroups' => __('Orders, PCs.')." - ".__('by UTM Groups'),
            'ordersSumSuccessByGroups' => __('Orders, rub.')." - ".__('by UTM Groups'),
            'ordersQuantitySuccessByDevices' => __('Orders, PCs.')." - ".__('by Devices'),
            'ordersSumSuccessByDevices' => __('Orders, rub.')." - ".__('by Devices'),
            'ordersQuantitySuccessByAges' => __('Orders, PCs.')." - ".__('by Ages'),
            'ordersSumSuccessByAges' => __('Orders, rub.')." - ".__('by Ages'),
            'ordersQuantitySuccessByGenders' => __('Orders, PCs.')." - ".__('by Genders'),
            'ordersSumSuccessByGenders' => __('Orders, rub.')." - ".__('by Genders'),
            'clicksQuantityByChannels' => __('Clicks, PCs.')." - ".__('by Channels'),
            'clicksSumByChannels' => __('Clicks, rub.')." - ".__('by Channels'),
            'clicksQuantityBySources' => __('Clicks, PCs.')." - ".__('by UTM Sources'),
            'clicksSumBySources' => __('Clicks, rub.')." - ".__('by UTM Sources'),
            'clicksQuantityByGroups' => __('Clicks, PCs.')." - ".__('by UTM Groups'),
            'clicksSumByGroups' => __('Clicks, rub.')." - ".__('by UTM Groups'),
            'clicksQuantityByDevices' => __('Clicks, PCs.')." - ".__('by Devices'),
            'clicksSumByDevices' => __('Clicks, rub.')." - ".__('by Devices'),
            'clicksQuantityByAges' => __('Clicks, PCs.')." - ".__('by Ages'),
            'clicksSumByAges' => __('Clicks, rub.')." - ".__('by Ages'),
            'clicksQuantityByGenders' => __('Clicks, PCs.')." - ".__('by Genders'),
            'clicksSumByGenders' => __('Clicks, rub.')." - ".__('by Genders'),
            'conversionsSumByChannels' => __('Conversions cost, rub.')." - ".__('by Channels'),
            'conversionsSumBySources' => __('Conversions cost, rub.')." - ".__('by UTM Sources'),
            'conversionsSumByGroups' => __('Conversions cost, rub.')." - ".__('by UTM Groups'),
            'conversionsSumByDevices' => __('Conversions cost, rub.')." - ".__('by Devices'),
            'conversionsSumByAges' => __('Conversions cost, rub.')." - ".__('by Ages'),
            'conversionsSumByGenders' => __('Conversions cost, rub.')." - ".__('by Genders'),
            'costsPercentByChannels' => __('Costs, %')." - ".__('by Channels'),
            'costsPercentBySources' => __('Costs, %')." - ".__('by UTM Sources'),
            'costsPercentByGroups' => __('Costs, %')." - ".__('by UTM Groups'),
            'costsPercentByDevices' => __('Costs, %')." - ".__('by Devices'),
            'costsPercentByAges' => __('Costs, %')." - ".__('by Ages'),
            'costsPercentByGenders' => __('Costs, %')." - ".__('by Genders'),
        ];

        //region подготовка данных для графиков "заказы"

        $ordersQuantityColors = $colors;
        $ordersSumColors = $colors;

        $ordersQuantityDataSetColor = self::hexToRGBA(array_shift($ordersQuantityColors), 0.2);

        $ordersQuantityDataSet = [
            'label' => __('All'),
            'data' => $periodTemplate,
            'backgroundColor' => $ordersQuantityDataSetColor,
            'borderColor' => $ordersQuantityDataSetColor,
            'fill' => false,
        ];

        $ordersSumDataSetColor = self::hexToRGBA(array_shift($ordersSumColors), 0.2);

        $ordersSumDataSet = [
            'label' => __('All'),
            'data' => $periodTemplate,
            'backgroundColor' => $ordersSumDataSetColor,
            'borderColor' => $ordersSumDataSetColor,
            'fill' => false,
        ];

        $ordersAll
            ->groupBy(
                function (Collection $item) {
                    return Carbon::createFromTimestamp($item->get('created_at'))->format('d-m');
                }
            )
            ->each(
                function (Collection $item, $key) use (&$ordersQuantityDataSet, &$ordersSumDataSet) {
                    $ordersQuantityDataSet['data'][$key] = $item->count();
                    $ordersSumDataSet['data'][$key] = $item->sum('sum');
                }
            );

        $ordersQuantityDataSet['data'] = explode(',', implode(',', $ordersQuantityDataSet['data']));
        $ordersSumDataSet['data'] = explode(',', implode(',', $ordersSumDataSet['data']));

        $dataSets['ordersQuantity'] = array_prepend($dataSets['ordersQuantity'], $ordersQuantityDataSet);
        $dataSets['ordersSum'] = array_prepend($dataSets['ordersSum'], $ordersSumDataSet);

        $ordersQuantityDataSetColor = array_shift($ordersQuantityColors);

        $ordersQuantityDataSet = [
            'label' => __('Success'),
            'data' => $periodTemplate,
            'backgroundColor' => $ordersQuantityDataSetColor,
            'borderColor' => $ordersQuantityDataSetColor,
            'fill' => false,
            'type' => 'line',
        ];

        $ordersSumDataSetColor = array_shift($ordersSumColors);

        $ordersSumDataSet = [
            'label' => __('Success'),
            'data' => $periodTemplate,
            'backgroundColor' => $ordersSumDataSetColor,
            'borderColor' => $ordersSumDataSetColor,
            'fill' => false,
            'type' => 'line',
        ];

        $ordersSuccess
            ->groupBy(
                function (Collection $item) {
                    return Carbon::createFromTimestamp($item->get('created_at'))->format('d-m');
                }
            )
            ->each(
                function (Collection $item, $key) use (&$ordersQuantityDataSet, &$ordersSumDataSet) {
                    $ordersQuantityDataSet['data'][$key] = $item->count();
                    $ordersSumDataSet['data'][$key] = $item->sum('sum');
                }
            );

        $ordersQuantityDataSet['data'] = explode(',', implode(',', $ordersQuantityDataSet['data']));
        $ordersSumDataSet['data'] = explode(',', implode(',', $ordersSumDataSet['data']));

        $dataSets['ordersQuantity'] = array_prepend($dataSets['ordersQuantity'], $ordersQuantityDataSet);
        $dataSets['ordersSum'] = array_prepend($dataSets['ordersSum'], $ordersSumDataSet);

        $graphs['ordersQuantity'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersQuantity'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Orders, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersQuantity'] = json_encode($graphs['ordersQuantity']);

        $graphs['ordersSum'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersSum'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Orders, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersSum'] = json_encode($graphs['ordersSum']);
        //endregion

        //region подготовка данных для графиков "успешные заказы по источникам"

        $ordersQuantitySuccessByChannelsColors = $colors;
        $ordersSumSuccessByChannelsColors = $colors;

        $ordersQuantitySuccessByChannelsDataSetColor = array_shift($ordersQuantitySuccessByChannelsColors);
        $ordersQuantitySuccessByChannelsDataSetColor = self::hexToRGBA(
            $ordersQuantitySuccessByChannelsDataSetColor,
            0.2
        );

        $ordersQuantitySuccessByChannelsDataSet = [
            'label' => __('All'),
            'data' => $ordersQuantityDataSet['data'],
            'backgroundColor' => $ordersQuantitySuccessByChannelsDataSetColor,
            'borderColor' => $ordersQuantitySuccessByChannelsDataSetColor,
            'fill' => false,
        ];

        $ordersSumSuccessByChannelsDataSetColor = array_shift($ordersSumSuccessByChannelsColors);
        $ordersSumSuccessByChannelsDataSetColor = self::hexToRGBA($ordersSumSuccessByChannelsDataSetColor, 0.2);

        $ordersSumSuccessByChannelsDataSet = [
            'label' => __('All'),
            'data' => $ordersSumDataSet['data'],
            'backgroundColor' => $ordersSumSuccessByChannelsDataSetColor,
            'borderColor' => $ordersSumSuccessByChannelsDataSetColor,
            'fill' => false,
        ];

        $dataSets['ordersQuantitySuccessByChannels'] = array_prepend(
            $dataSets['ordersQuantitySuccessByChannels'],
            $ordersQuantitySuccessByChannelsDataSet
        );
        $dataSets['ordersSumSuccessByChannels'] = array_prepend(
            $dataSets['ordersSumSuccessByChannels'],
            $ordersSumSuccessByChannelsDataSet
        );


        $ordersSuccess
            ->groupBy(
                function (Collection $item) {
                    return $item->get('order')->channel->name;
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$ordersQuantitySuccessByChannelsColors,
                    &$ordersSumSuccessByChannelsColors,
                    $periodTemplate
                ) {
                    $ordersQuantitySuccessByChannelsDataSetColor = array_shift($ordersQuantitySuccessByChannelsColors);

                    $ordersQuantitySuccessByChannelsDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersQuantitySuccessByChannelsDataSetColor,
                        'borderColor' => $ordersQuantitySuccessByChannelsDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $ordersSumSuccessByChannelsDataSetColor = array_shift($ordersSumSuccessByChannelsColors);

                    $ordersSumSuccessByChannelsDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersSumSuccessByChannelsDataSetColor,
                        'borderColor' => $ordersSumSuccessByChannelsDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromTimestamp($item->get('created_at'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$ordersQuantitySuccessByChannelsDataSet,
                                &
                                $ordersSumSuccessByChannelsDataSet
                            ) {
                                $ordersQuantitySuccessByChannelsDataSet['data'][$key] = $item->count();
                                $ordersSumSuccessByChannelsDataSet['data'][$key] = $item->sum('sum');
                            }
                        );

                    $ordersQuantitySuccessByChannelsDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersQuantitySuccessByChannelsDataSet['data'])
                    );
                    $ordersSumSuccessByChannelsDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersSumSuccessByChannelsDataSet['data'])
                    );

                    $dataSets['ordersQuantitySuccessByChannels'] = array_prepend(
                        $dataSets['ordersQuantitySuccessByChannels'],
                        $ordersQuantitySuccessByChannelsDataSet
                    );
                    $dataSets['ordersSumSuccessByChannels'] = array_prepend(
                        $dataSets['ordersSumSuccessByChannels'],
                        $ordersSumSuccessByChannelsDataSet
                    );

                }
            );


        $graphs['ordersQuantitySuccessByChannels'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersQuantitySuccessByChannels'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersQuantitySuccessByChannels'] = json_encode($graphs['ordersQuantitySuccessByChannels']);

        $graphs['ordersSumSuccessByChannels'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersSumSuccessByChannels'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersSumSuccessByChannels'] = json_encode($graphs['ordersSumSuccessByChannels']);

        //endregion

        //region подготовка данных для графиков "успешные заказы по UTM Source"

        $ordersQuantitySuccessBySourcesColors = $colors;
        $ordersSumSuccessBySourcesColors = $colors;

        $ordersQuantitySuccessBySourcesDataSetColor = array_shift($ordersQuantitySuccessBySourcesColors);
        $ordersQuantitySuccessBySourcesDataSetColor = self::hexToRGBA(
            $ordersQuantitySuccessBySourcesDataSetColor,
            0.2
        );

        $ordersQuantitySuccessBySourcesDataSet = [
            'label' => __('All'),
            'data' => $ordersQuantityDataSet['data'],
            'backgroundColor' => $ordersQuantitySuccessBySourcesDataSetColor,
            'borderColor' => $ordersQuantitySuccessBySourcesDataSetColor,
            'fill' => false,
        ];

        $ordersSumSuccessBySourcesDataSetColor = array_shift($ordersSumSuccessBySourcesColors);
        $ordersSumSuccessBySourcesDataSetColor = self::hexToRGBA($ordersSumSuccessBySourcesDataSetColor, 0.2);

        $ordersSumSuccessBySourcesDataSet = [
            'label' => __('All'),
            'data' => $ordersSumDataSet['data'],
            'backgroundColor' => $ordersSumSuccessBySourcesDataSetColor,
            'borderColor' => $ordersSumSuccessBySourcesDataSetColor,
            'fill' => false,
        ];

        $dataSets['ordersQuantitySuccessBySources'] = array_prepend(
            $dataSets['ordersQuantitySuccessBySources'],
            $ordersQuantitySuccessBySourcesDataSet
        );
        $dataSets['ordersSumSuccessBySources'] = array_prepend(
            $dataSets['ordersSumSuccessBySources'],
            $ordersSumSuccessBySourcesDataSet
        );


        $ordersSuccess
            ->groupBy(
                function (Collection $item) {

                    /**
                     * @var Order $order
                     */
                    $order = $item->get('order');

                    if (!is_object($order->utm) || $order->utm->source == '') {
                        return __('-- No UTM --');
                    }

                    if (preg_match('/[\{\}&\?\=]+/', $order->utm->source)) {
                        return __('-- Error UTM --');
                    }

                    return $order->utm->source;
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$ordersQuantitySuccessBySourcesColors,
                    &$ordersSumSuccessBySourcesColors,
                    $periodTemplate
                ) {
                    $ordersQuantitySuccessBySourcesDataSetColor = array_shift($ordersQuantitySuccessBySourcesColors);

                    $ordersQuantitySuccessBySourcesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersQuantitySuccessBySourcesDataSetColor,
                        'borderColor' => $ordersQuantitySuccessBySourcesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $ordersSumSuccessBySourcesDataSetColor = array_shift($ordersSumSuccessBySourcesColors);

                    $ordersSumSuccessBySourcesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersSumSuccessBySourcesDataSetColor,
                        'borderColor' => $ordersSumSuccessBySourcesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromTimestamp($item->get('created_at'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$ordersQuantitySuccessBySourcesDataSet,
                                &
                                $ordersSumSuccessBySourcesDataSet
                            ) {
                                $ordersQuantitySuccessBySourcesDataSet['data'][$key] = $item->count();
                                $ordersSumSuccessBySourcesDataSet['data'][$key] = $item->sum('sum');
                            }
                        );

                    $ordersQuantitySuccessBySourcesDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersQuantitySuccessBySourcesDataSet['data'])
                    );
                    $ordersSumSuccessBySourcesDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersSumSuccessBySourcesDataSet['data'])
                    );

                    $dataSets['ordersQuantitySuccessBySources'] = array_prepend(
                        $dataSets['ordersQuantitySuccessBySources'],
                        $ordersQuantitySuccessBySourcesDataSet
                    );
                    $dataSets['ordersSumSuccessBySources'] = array_prepend(
                        $dataSets['ordersSumSuccessBySources'],
                        $ordersSumSuccessBySourcesDataSet
                    );

                }
            );


        $graphs['ordersQuantitySuccessBySources'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersQuantitySuccessBySources'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersQuantitySuccessBySources'] = json_encode($graphs['ordersQuantitySuccessBySources']);

        $graphs['ordersSumSuccessBySources'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersSumSuccessBySources'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersSumSuccessBySources'] = json_encode($graphs['ordersSumSuccessBySources']);

        //endregion

        //region подготовка данных для графиков "успешные заказы по UTM Group"

        $ordersQuantitySuccessByGroupsColors = $colors;
        $ordersSumSuccessByGroupsColors = $colors;

        $acc = collect();
        $ordersSuccess
            ->groupBy(
                function (Collection $item) {
                    /**
                     * @var Order $order
                     */
                    $order = $item->get('order');

                    $utmString = ($order->utm ?? '') == '' || $order->utm->campaign == '' ? 'utm no' : "{$order->utm->campaign}/{$order->utm->source}";

                    $testString = "::{$order->channel->name}::{$utmString}";

                    return $testString;
                }
            )
            ->each(
                function (Collection $items, string $key) use ($utm_groups, &$acc) {

                    $testString = $key;

                    $utmGroups = UtmGroup::getGroupsForString($testString);

                    if (empty($utm_groups) && empty($utmGroups)) {
                        $accGroup = $acc->get(__('-- No Group --'), collect());

                        foreach ($items as $item) {
                            /**
                             * @var Order $order
                             */
                            $order = $item->get('order');
                            $accGroup->put($order->id, $item);
                        }

                        $acc->put(__('-- No Group --'), $accGroup);
                    }

                    /**
                     * @var UtmGroup $UTMGroup
                     */

                    foreach (UtmGroup::getGroupsForString($testString) as $UTMGroup) {

                        if (empty($utm_groups) || in_array($UTMGroup->id, $utm_groups)) {
                            $accGroup = $acc->get($UTMGroup->name, collect());
                            foreach ($items as $item) {
                                /**
                                 * @var Order $order
                                 */
                                $order = $item->get('order');
                                $accGroup->put($order->id, $item);
                            }
                            $acc->put($UTMGroup->name, $accGroup);
                        }


                    }
                }
            );

        $acc->sortKeysDesc()->each(
            function (Collection $item, $key) use (
                &$dataSets,
                &$ordersQuantitySuccessByGroupsColors,
                &$ordersSumSuccessByGroupsColors,
                $periodTemplate
            ) {
                $ordersQuantitySuccessByGroupsDataSetColor = array_shift($ordersQuantitySuccessByGroupsColors);

                $ordersQuantitySuccessByGroupsDataSet = [
                    'label' => $key,
                    'data' => $periodTemplate,
                    'backgroundColor' => $ordersQuantitySuccessByGroupsDataSetColor,
                    'borderColor' => $ordersQuantitySuccessByGroupsDataSetColor,
                    'fill' => false,
                    'hidden' => true,
                ];

                $ordersSumSuccessByGroupsDataSetColor = array_shift($ordersSumSuccessByGroupsColors);

                $ordersSumSuccessByGroupsDataSet = [
                    'label' => $key,
                    'data' => $periodTemplate,
                    'backgroundColor' => $ordersSumSuccessByGroupsDataSetColor,
                    'borderColor' => $ordersSumSuccessByGroupsDataSetColor,
                    'fill' => false,
                    'hidden' => true,
                ];

                $item->groupBy(
                    function (Collection $item) {
                        return Carbon::createFromTimestamp($item->get('created_at'))->format('d-m');
                    }
                )
                    ->each(
                        function (Collection $item, $key) use (
                            &$ordersQuantitySuccessByGroupsDataSet,
                            &
                            $ordersSumSuccessByGroupsDataSet
                        ) {
                            $ordersQuantitySuccessByGroupsDataSet['data'][$key] = $item->count();
                            $ordersSumSuccessByGroupsDataSet['data'][$key] = $item->sum('sum');
                        }
                    );

                $ordersQuantitySuccessByGroupsDataSet['data'] = explode(
                    ',',
                    implode(',', $ordersQuantitySuccessByGroupsDataSet['data'])
                );
                $ordersSumSuccessByGroupsDataSet['data'] = explode(
                    ',',
                    implode(',', $ordersSumSuccessByGroupsDataSet['data'])
                );

                $dataSets['ordersQuantitySuccessByGroups'] = array_prepend(
                    $dataSets['ordersQuantitySuccessByGroups'],
                    $ordersQuantitySuccessByGroupsDataSet
                );
                $dataSets['ordersSumSuccessByGroups'] = array_prepend(
                    $dataSets['ordersSumSuccessByGroups'],
                    $ordersSumSuccessByGroupsDataSet
                );

            }
        );

        unset($acc);


        $graphs['ordersQuantitySuccessByGroups'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersQuantitySuccessByGroups'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersQuantitySuccessByGroups'] = json_encode($graphs['ordersQuantitySuccessByGroups']);

        $graphs['ordersSumSuccessByGroups'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersSumSuccessByGroups'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersSumSuccessByGroups'] = json_encode($graphs['ordersSumSuccessByGroups']);

        //endregion

        //region подготовка данных для графиков "успешные заказы по устройствам"

        $ordersQuantitySuccessByDevicesColors = $colors;
        $ordersSumSuccessByDevicesColors = $colors;

        $ordersQuantitySuccessByDevicesDataSetColor = array_shift($ordersQuantitySuccessByDevicesColors);
        $ordersQuantitySuccessByDevicesDataSetColor = self::hexToRGBA(
            $ordersQuantitySuccessByDevicesDataSetColor,
            0.2
        );

        $ordersQuantitySuccessByDevicesDataSet = [
            'label' => __('All'),
            'data' => $ordersQuantityDataSet['data'],
            'backgroundColor' => $ordersQuantitySuccessByDevicesDataSetColor,
            'borderColor' => $ordersQuantitySuccessByDevicesDataSetColor,
            'fill' => false,
        ];

        $ordersSumSuccessByDevicesDataSetColor = array_shift($ordersSumSuccessByDevicesColors);
        $ordersSumSuccessByDevicesDataSetColor = self::hexToRGBA($ordersSumSuccessByDevicesDataSetColor, 0.2);

        $ordersSumSuccessByDevicesDataSet = [
            'label' => __('All'),
            'data' => $ordersSumDataSet['data'],
            'backgroundColor' => $ordersSumSuccessByDevicesDataSetColor,
            'borderColor' => $ordersSumSuccessByDevicesDataSetColor,
            'fill' => false,
        ];

        $dataSets['ordersQuantitySuccessByDevices'] = array_prepend(
            $dataSets['ordersQuantitySuccessByDevices'],
            $ordersQuantitySuccessByDevicesDataSet
        );
        $dataSets['ordersSumSuccessByDevices'] = array_prepend(
            $dataSets['ordersSumSuccessByDevices'],
            $ordersSumSuccessByDevicesDataSet
        );


        $ordersSuccess
            ->groupBy(
                function (Collection $item) use ($devicesGroups) {

                    /**
                     * @var Order $order
                     */
                    $order = $item->get('order');

                    if (($order->device ?? '') == '' || !isset($devicesGroups[$order->device])) {
                        return __('-- No Device --');
                    }

                    return $devicesGroups[$order->device];
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$ordersQuantitySuccessByDevicesColors,
                    &$ordersSumSuccessByDevicesColors,
                    $periodTemplate
                ) {
                    $ordersQuantitySuccessByDevicesDataSetColor = array_shift($ordersQuantitySuccessByDevicesColors);

                    $ordersQuantitySuccessByDevicesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersQuantitySuccessByDevicesDataSetColor,
                        'borderColor' => $ordersQuantitySuccessByDevicesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $ordersSumSuccessByDevicesDataSetColor = array_shift($ordersSumSuccessByDevicesColors);

                    $ordersSumSuccessByDevicesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersSumSuccessByDevicesDataSetColor,
                        'borderColor' => $ordersSumSuccessByDevicesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromTimestamp($item->get('created_at'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$ordersQuantitySuccessByDevicesDataSet,
                                &
                                $ordersSumSuccessByDevicesDataSet
                            ) {
                                $ordersQuantitySuccessByDevicesDataSet['data'][$key] = $item->count();
                                $ordersSumSuccessByDevicesDataSet['data'][$key] = $item->sum('sum');
                            }
                        );

                    $ordersQuantitySuccessByDevicesDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersQuantitySuccessByDevicesDataSet['data'])
                    );
                    $ordersSumSuccessByDevicesDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersSumSuccessByDevicesDataSet['data'])
                    );

                    $dataSets['ordersQuantitySuccessByDevices'] = array_prepend(
                        $dataSets['ordersQuantitySuccessByDevices'],
                        $ordersQuantitySuccessByDevicesDataSet
                    );
                    $dataSets['ordersSumSuccessByDevices'] = array_prepend(
                        $dataSets['ordersSumSuccessByDevices'],
                        $ordersSumSuccessByDevicesDataSet
                    );

                }
            );


        $graphs['ordersQuantitySuccessByDevices'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersQuantitySuccessByDevices'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersQuantitySuccessByDevices'] = json_encode($graphs['ordersQuantitySuccessByDevices']);

        $graphs['ordersSumSuccessByDevices'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersSumSuccessByDevices'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersSumSuccessByDevices'] = json_encode($graphs['ordersSumSuccessByDevices']);

        //endregion

        //region подготовка данных для графиков "успешные заказы по возрасту"

        $ordersQuantitySuccessByAgesColors = $colors;
        $ordersSumSuccessByAgesColors = $colors;

        $ordersQuantitySuccessByAgesDataSetColor = array_shift($ordersQuantitySuccessByAgesColors);
        $ordersQuantitySuccessByAgesDataSetColor = self::hexToRGBA(
            $ordersQuantitySuccessByAgesDataSetColor,
            0.2
        );

        $ordersQuantitySuccessByAgesDataSet = [
            'label' => __('All'),
            'data' => $ordersQuantityDataSet['data'],
            'backgroundColor' => $ordersQuantitySuccessByAgesDataSetColor,
            'borderColor' => $ordersQuantitySuccessByAgesDataSetColor,
            'fill' => false,
        ];

        $ordersSumSuccessByAgesDataSetColor = array_shift($ordersSumSuccessByAgesColors);
        $ordersSumSuccessByAgesDataSetColor = self::hexToRGBA($ordersSumSuccessByAgesDataSetColor, 0.2);

        $ordersSumSuccessByAgesDataSet = [
            'label' => __('All'),
            'data' => $ordersSumDataSet['data'],
            'backgroundColor' => $ordersSumSuccessByAgesDataSetColor,
            'borderColor' => $ordersSumSuccessByAgesDataSetColor,
            'fill' => false,
        ];

        $dataSets['ordersQuantitySuccessByAges'] = array_prepend(
            $dataSets['ordersQuantitySuccessByAges'],
            $ordersQuantitySuccessByAgesDataSet
        );
        $dataSets['ordersSumSuccessByAges'] = array_prepend(
            $dataSets['ordersSumSuccessByAges'],
            $ordersSumSuccessByAgesDataSet
        );


        $ordersSuccess
            ->groupBy(
                function (Collection $item) use ($agesGroups) {

                    /**
                     * @var Order $order
                     */
                    $order = $item->get('order');

                    if (($order->age ?? '') == '' || !isset($agesGroups[$order->age])) {
                        return __('-- No Age --');
                    }

                    return $agesGroups[$order->age];
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$ordersQuantitySuccessByAgesColors,
                    &$ordersSumSuccessByAgesColors,
                    $periodTemplate
                ) {
                    $ordersQuantitySuccessByAgesDataSetColor = array_shift($ordersQuantitySuccessByAgesColors);

                    $ordersQuantitySuccessByAgesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersQuantitySuccessByAgesDataSetColor,
                        'borderColor' => $ordersQuantitySuccessByAgesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $ordersSumSuccessByAgesDataSetColor = array_shift($ordersSumSuccessByAgesColors);

                    $ordersSumSuccessByAgesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersSumSuccessByAgesDataSetColor,
                        'borderColor' => $ordersSumSuccessByAgesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromTimestamp($item->get('created_at'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$ordersQuantitySuccessByAgesDataSet,
                                &
                                $ordersSumSuccessByAgesDataSet
                            ) {
                                $ordersQuantitySuccessByAgesDataSet['data'][$key] = $item->count();
                                $ordersSumSuccessByAgesDataSet['data'][$key] = $item->sum('sum');
                            }
                        );

                    $ordersQuantitySuccessByAgesDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersQuantitySuccessByAgesDataSet['data'])
                    );
                    $ordersSumSuccessByAgesDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersSumSuccessByAgesDataSet['data'])
                    );

                    $dataSets['ordersQuantitySuccessByAges'] = array_prepend(
                        $dataSets['ordersQuantitySuccessByAges'],
                        $ordersQuantitySuccessByAgesDataSet
                    );
                    $dataSets['ordersSumSuccessByAges'] = array_prepend(
                        $dataSets['ordersSumSuccessByAges'],
                        $ordersSumSuccessByAgesDataSet
                    );

                }
            );


        $graphs['ordersQuantitySuccessByAges'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersQuantitySuccessByAges'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersQuantitySuccessByAges'] = json_encode($graphs['ordersQuantitySuccessByAges']);

        $graphs['ordersSumSuccessByAges'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersSumSuccessByAges'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersSumSuccessByAges'] = json_encode($graphs['ordersSumSuccessByAges']);

        //endregion

        //region подготовка данных для графиков "успешные заказы по полу"

        $ordersQuantitySuccessByGendersColors = $colors;
        $ordersSumSuccessByGendersColors = $colors;

        $ordersQuantitySuccessByGendersDataSetColor = array_shift($ordersQuantitySuccessByGendersColors);
        $ordersQuantitySuccessByGendersDataSetColor = self::hexToRGBA(
            $ordersQuantitySuccessByGendersDataSetColor,
            0.2
        );

        $ordersQuantitySuccessByGendersDataSet = [
            'label' => __('All'),
            'data' => $ordersQuantityDataSet['data'],
            'backgroundColor' => $ordersQuantitySuccessByGendersDataSetColor,
            'borderColor' => $ordersQuantitySuccessByGendersDataSetColor,
            'fill' => false,
        ];

        $ordersSumSuccessByGendersDataSetColor = array_shift($ordersSumSuccessByGendersColors);
        $ordersSumSuccessByGendersDataSetColor = self::hexToRGBA($ordersSumSuccessByGendersDataSetColor, 0.2);

        $ordersSumSuccessByGendersDataSet = [
            'label' => __('All'),
            'data' => $ordersSumDataSet['data'],
            'backgroundColor' => $ordersSumSuccessByGendersDataSetColor,
            'borderColor' => $ordersSumSuccessByGendersDataSetColor,
            'fill' => false,
        ];

        $dataSets['ordersQuantitySuccessByGenders'] = array_prepend(
            $dataSets['ordersQuantitySuccessByGenders'],
            $ordersQuantitySuccessByGendersDataSet
        );
        $dataSets['ordersSumSuccessByGenders'] = array_prepend(
            $dataSets['ordersSumSuccessByGenders'],
            $ordersSumSuccessByGendersDataSet
        );


        $ordersSuccess
            ->groupBy(
                function (Collection $item) use ($gendersGroups) {

                    /**
                     * @var Order $order
                     */
                    $order = $item->get('order');

                    if (($order->gender ?? '') == '' || !isset($gendersGroups[$order->gender])) {
                        return __('-- No Gender--');
                    }

                    return $gendersGroups[$order->gender];
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$ordersQuantitySuccessByGendersColors,
                    &$ordersSumSuccessByGendersColors,
                    $periodTemplate
                ) {
                    $ordersQuantitySuccessByGendersDataSetColor = array_shift($ordersQuantitySuccessByGendersColors);

                    $ordersQuantitySuccessByGendersDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersQuantitySuccessByGendersDataSetColor,
                        'borderColor' => $ordersQuantitySuccessByGendersDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $ordersSumSuccessByGendersDataSetColor = array_shift($ordersSumSuccessByGendersColors);

                    $ordersSumSuccessByGendersDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $ordersSumSuccessByGendersDataSetColor,
                        'borderColor' => $ordersSumSuccessByGendersDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromTimestamp($item->get('created_at'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$ordersQuantitySuccessByGendersDataSet,
                                &
                                $ordersSumSuccessByGendersDataSet
                            ) {
                                $ordersQuantitySuccessByGendersDataSet['data'][$key] = $item->count();
                                $ordersSumSuccessByGendersDataSet['data'][$key] = $item->sum('sum');
                            }
                        );

                    $ordersQuantitySuccessByGendersDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersQuantitySuccessByGendersDataSet['data'])
                    );
                    $ordersSumSuccessByGendersDataSet['data'] = explode(
                        ',',
                        implode(',', $ordersSumSuccessByGendersDataSet['data'])
                    );

                    $dataSets['ordersQuantitySuccessByGenders'] = array_prepend(
                        $dataSets['ordersQuantitySuccessByGenders'],
                        $ordersQuantitySuccessByGendersDataSet
                    );
                    $dataSets['ordersSumSuccessByGenders'] = array_prepend(
                        $dataSets['ordersSumSuccessByGenders'],
                        $ordersSumSuccessByGendersDataSet
                    );

                }
            );


        $graphs['ordersQuantitySuccessByGenders'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersQuantitySuccessByGenders'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersQuantitySuccessByGenders'] = json_encode($graphs['ordersQuantitySuccessByGenders']);

        $graphs['ordersSumSuccessByGenders'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['ordersSumSuccessByGenders'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Success orders, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['ordersSumSuccessByGenders'] = json_encode($graphs['ordersSumSuccessByGenders']);

        //endregion


        $clicksAll = collect();

        Channel::each(
            function (Channel $channel) use (&$clicksAll, $db, $dateFrom, $dateTo) {
                if (!($channel->yandex_counter ?? 0)) {
                    return true;
                }

                $dbName = "db_{$channel->yandex_counter}_ya_counter";

                if (!$db->isDatabaseExist($dbName)) {
                    return true;
                }

                if (!$db->isExists($dbName, 'costs')
                    || !$db->isExists($dbName, 'additional')
                    || !$db->isExists($dbName, 'hits')) {
                    return true;
                }

                $sqlWhere = [];

                if (!is_null($dateFrom)) {
                    $dateFrom = Carbon::createFromFormat('d-m-Y', $dateFrom)->format('Y-m-d');
                    $sqlWhere[] = "c.Date >= '{$dateFrom}'";
                }

                if (!is_null($dateTo)) {
                    $dateTo = Carbon::createFromFormat('d-m-Y', $dateTo)->format('Y-m-d');
                    $sqlWhere[] = "c.Date <= '{$dateTo}'";
                }

                $sqlWhere = (!empty($sqlWhere)) ? ' WHERE '.implode(' AND ', $sqlWhere) : '';

                $clicks = $db->select(
                    "SELECT c.Date as Date, c.UTMCampaign as UTMCampaign, c.UTMSource as UTMSource, c.Cost as Cost, a.AgeInterval as AgeInterval, a.Gender as Gender, h.DeviceCategory as DeviceCategory FROM {$dbName}.costs c
                    LEFT JOIN (SELECT DISTINCT WatchID, AgeInterval, Gender FROM {$dbName}.additional GROUP BY WatchID, AgeInterval, Gender) a ON (a.WatchID = c.WatchID)
                    LEFT JOIN (SELECT DISTINCT WatchID, DeviceCategory FROM {$dbName}.hits GROUP BY WatchID, DeviceCategory) h ON (h.WatchID = c.WatchID){$sqlWhere}"
                )->rows();

                if (empty($clicks)) {
                    return true;
                }

                foreach ($clicks as $click) {
                    $click['channel'] = $channel->name;

                    switch ($click['DeviceCategory']) {
                        case '1':
                            $click['DeviceCategory'] = 'desktop';
                            break;
                        case '2':
                            $click['DeviceCategory'] = 'mobile';
                            break;
                        case '3':
                            $click['DeviceCategory'] = 'tablet';
                            break;
                    }

                    $clicksAll->push(collect($click));
                }

                return true;
            }
        );


        $clicksAll = $clicksAll->filter(
            function (Collection $click) use ($utm_campaigns, $utm_sources, $utm_groups, $devices, $ages, $genders) {

                $utmCampaign = $click->get('UTMCampaign');

                if (!empty($utm_campaigns)) {
                    if ($utmCampaign == '') {
                        if (!in_array(__('-- No UTM --'), $utm_campaigns)) {
                            return false;
                        }
                    } elseif (!in_array($utmCampaign, $utm_campaigns)) {
                        if (in_array(__('-- Error UTM --'), $utm_campaigns) && preg_match(
                                '/[\{\}&\?\=]+/',
                                $utmCampaign
                            )) {
                            goto utmSources;
                        }

                        return false;
                    }
                }

                utmSources:
                $utmSource = $click->get('UTMSource');

                if (!empty($utm_sources)) {
                    if ($utmSource == '') {
                        if (!in_array(__('-- No UTM --'), $utm_sources)) {
                            return false;
                        }
                    } elseif (!in_array($utmSource, $utm_sources)) {
                        if (in_array(__('-- Error UTM --'), $utm_sources) && preg_match('/[\{\}&\?\=]+/', $utmSource)) {
                            goto utmGroups;
                        }

                        return false;
                    }
                }

                utmGroups:
                if (!empty($utm_groups)) {

                    $utmString = $utmCampaign == '' ? 'utm no' : "{$utmCampaign}/{$utmSource}";

                    $utmGroupTestString = "::{$click->get('channel')}::{$utmString}";

                    /**
                     * @var UtmGroup $utmGroup
                     */
                    foreach (UtmGroup::getGroupsForString($utmGroupTestString) as $utmGroup) {
                        if (in_array($utmGroup->id, $utm_groups)) {
                            goto devices;
                        }
                    }

                    return false;
                }

                devices:
                if (!empty($devices) && !in_array($click->get('DeviceCategory'), $devices)) {
                    return false;
                }

                if (!empty($ages) && !in_array($click->get('AgeInterval'), $ages)) {
                    return false;
                }

                if (!empty($genders) && !in_array($click->get('Gender'), $genders)) {
                    return false;
                }

                return true;

            }
        );

        $usdRate = self::getUsdRate();

        //region подготовка данных для графиков "клики по источникам"

        $clicksQuantityByChannelsColors = $colors;
        $clicksSumByChannelsColors = $colors;

        $clicksQuantityByChannelsDataSetColor = self::hexToRGBA(array_shift($clicksQuantityByChannelsColors), 0.2);

        $clicksQuantityByChannelsDataSet = [
            'label' => __('All'),
            'data' => $periodTemplate,
            'backgroundColor' => $clicksQuantityByChannelsDataSetColor,
            'borderColor' => $clicksQuantityByChannelsDataSetColor,
            'fill' => false,
        ];

        $clicksSumByChannelsDataSetColor = self::hexToRGBA(array_shift($clicksSumByChannelsColors), 0.2);

        $clicksSumByChannelsDataSet = [
            'label' => __('All'),
            'data' => $periodTemplate,
            'backgroundColor' => $clicksSumByChannelsDataSetColor,
            'borderColor' => $clicksSumByChannelsDataSetColor,
            'fill' => false,
        ];

        $clicksAll
            ->groupBy(
                function (Collection $item) {
                    return Carbon::createFromFormat('Y-m-d', $item->get('Date'))->format('d-m');
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$clicksQuantityByChannelsDataSet,
                    &$clicksSumByChannelsDataSet,
                    $usdRate
                ) {
                    $clicksQuantityByChannelsDataSet['data'][$key] = $item->count();
                    $clicksSumByChannelsDataSet['data'][$key] = round($item->sum('Cost') * $usdRate / 100000, 2);
                }
            );

        $clicksQuantityByChannelsDataSet['data'] = explode(',', implode(',', $clicksQuantityByChannelsDataSet['data']));
        $clicksSumByChannelsDataSet['data'] = explode(',', implode(',', $clicksSumByChannelsDataSet['data']));

        $dataSets['clicksQuantityByChannels'] = array_prepend(
            $dataSets['clicksQuantityByChannels'],
            $clicksQuantityByChannelsDataSet
        );
        $dataSets['clicksSumByChannels'] = array_prepend($dataSets['clicksSumByChannels'], $clicksSumByChannelsDataSet);

        $clicksAll
            ->groupBy(
                function (Collection $item) {
                    return $item->get('channel');
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$clicksQuantityByChannelsColors,
                    &$clicksSumByChannelsColors,
                    $periodTemplate,
                    $usdRate
                ) {
                    $clicksQuantityByChannelsDataSetColor = array_shift($clicksQuantityByChannelsColors);

                    $clicksQuantityByChannelsDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksQuantityByChannelsDataSetColor,
                        'borderColor' => $clicksQuantityByChannelsDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $clicksSumByChannelsDataSetColor = array_shift($clicksSumByChannelsColors);

                    $clicksSumByChannelsDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksSumByChannelsDataSetColor,
                        'borderColor' => $clicksSumByChannelsDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromFormat('Y-m-d', $item->get('Date'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$clicksQuantityByChannelsDataSet,
                                &
                                $clicksSumByChannelsDataSet,
                                $usdRate
                            ) {
                                $clicksQuantityByChannelsDataSet['data'][$key] = $item->count();
                                $clicksSumByChannelsDataSet['data'][$key] = round(
                                    $item->sum('Cost') * $usdRate / 100000,
                                    2
                                );
                            }
                        );

                    $clicksQuantityByChannelsDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksQuantityByChannelsDataSet['data'])
                    );
                    $clicksSumByChannelsDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksSumByChannelsDataSet['data'])
                    );

                    $dataSets['clicksQuantityByChannels'] = array_prepend(
                        $dataSets['clicksQuantityByChannels'],
                        $clicksQuantityByChannelsDataSet
                    );
                    $dataSets['clicksSumByChannels'] = array_prepend(
                        $dataSets['clicksSumByChannels'],
                        $clicksSumByChannelsDataSet
                    );

                }
            );


        $graphs['clicksQuantityByChannels'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksQuantityByChannels'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksQuantityByChannels'] = json_encode($graphs['clicksQuantityByChannels']);

        $graphs['clicksSumByChannels'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksSumByChannels'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksSumByChannels'] = json_encode($graphs['clicksSumByChannels']);

        //endregion

        //region подготовка данных для графиков "клики по UTM Source"

        $clicksQuantityBySourcesColors = $colors;
        $clicksSumBySourcesColors = $colors;

        $clicksQuantityBySourcesDataSetColor = self::hexToRGBA(array_shift($clicksQuantityBySourcesColors), 0.2);

        $clicksQuantityBySourcesDataSet = [
            'label' => __('All'),
            'data' => $clicksQuantityByChannelsDataSet['data'],
            'backgroundColor' => $clicksQuantityBySourcesDataSetColor,
            'borderColor' => $clicksQuantityBySourcesDataSetColor,
            'fill' => false,
        ];

        $clicksSumBySourcesDataSetColor = self::hexToRGBA(array_shift($clicksSumBySourcesColors), 0.2);

        $clicksSumBySourcesDataSet = [
            'label' => __('All'),
            'data' => $clicksSumByChannelsDataSet['data'],
            'backgroundColor' => $clicksSumBySourcesDataSetColor,
            'borderColor' => $clicksSumBySourcesDataSetColor,
            'fill' => false,
        ];

        $dataSets['clicksQuantityBySources'] = array_prepend(
            $dataSets['clicksQuantityBySources'],
            $clicksQuantityBySourcesDataSet
        );
        $dataSets['clicksSumBySources'] = array_prepend($dataSets['clicksSumBySources'], $clicksSumBySourcesDataSet);

        $clicksAll
            ->groupBy(
                function (Collection $item) {

                    $utmSource = $item->get('UTMSource');

                    if ($utmSource == '') {
                        return __('-- No UTM --');
                    }

                    if (preg_match('/[\{\}&\?\=]+/', $utmSource)) {
                        return __('-- Error UTM --');
                    }

                    return $utmSource;
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$clicksQuantityBySourcesColors,
                    &$clicksSumBySourcesColors,
                    $periodTemplate,
                    $usdRate
                ) {
                    $clicksQuantityBySourcesDataSetColor = array_shift($clicksQuantityBySourcesColors);

                    $clicksQuantityBySourcesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksQuantityBySourcesDataSetColor,
                        'borderColor' => $clicksQuantityBySourcesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $clicksSumBySourcesDataSetColor = array_shift($clicksSumBySourcesColors);

                    $clicksSumBySourcesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksSumBySourcesDataSetColor,
                        'borderColor' => $clicksSumBySourcesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromFormat('Y-m-d', $item->get('Date'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$clicksQuantityBySourcesDataSet,
                                &
                                $clicksSumBySourcesDataSet,
                                $usdRate
                            ) {
                                $clicksQuantityBySourcesDataSet['data'][$key] = $item->count();
                                $clicksSumBySourcesDataSet['data'][$key] = round(
                                    $item->sum('Cost') * $usdRate / 100000,
                                    2
                                );
                            }
                        );

                    $clicksQuantityBySourcesDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksQuantityBySourcesDataSet['data'])
                    );
                    $clicksSumBySourcesDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksSumBySourcesDataSet['data'])
                    );

                    $dataSets['clicksQuantityBySources'] = array_prepend(
                        $dataSets['clicksQuantityBySources'],
                        $clicksQuantityBySourcesDataSet
                    );
                    $dataSets['clicksSumBySources'] = array_prepend(
                        $dataSets['clicksSumBySources'],
                        $clicksSumBySourcesDataSet
                    );

                }
            );


        $graphs['clicksQuantityBySources'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksQuantityBySources'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksQuantityBySources'] = json_encode($graphs['clicksQuantityBySources']);

        $graphs['clicksSumBySources'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksSumBySources'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksSumBySources'] = json_encode($graphs['clicksSumBySources']);

        //endregion

        //region подготовка данных для графиков "клики по UTM Group"

        $clicksQuantityByGroupsColors = $colors;
        $clicksSumByGroupsColors = $colors;

        $acc = collect();

        $clicksAll
            ->groupBy(
                function (Collection $item) {

                    $channel = $item->get('channel');
                    $utmCampaign = $item->get('UTMCampaign');
                    $utmSource = $item->get('UTMSource');

                    $utmString = ($utmCampaign ?? '') == '' ? 'utm no' : "{$utmCampaign}/{$utmSource}";

                    $testString = "::{$channel}::{$utmString}";

                    return $testString;
                }
            )
            ->each(
                function (Collection $items, string $key) use ($utm_groups, &$acc) {

                    $testString = $key;

                    $utmGroups = UtmGroup::getGroupsForString($testString);

                    if (empty($utm_groups) && empty($utmGroups)) {
                        $accGroup = $acc->get(__('-- No Group --'), collect());
                        foreach ($items as $item) {
                            $accGroup->push($item);
                        }
                        $acc->put(__('-- No Group --'), $accGroup);
                    }

                    /**
                     * @var UtmGroup $UTMGroup
                     */

                    foreach (UtmGroup::getGroupsForString($testString) as $UTMGroup) {

                        if (empty($utm_groups) || in_array($UTMGroup->id, $utm_groups)) {
                            $accGroup = $acc->get($UTMGroup->name, collect());
                            foreach ($items as $item) {
                                $accGroup->push($item);
                            }
                            $acc->put($UTMGroup->name, $accGroup);
                        }


                    }
                }
            );

        $acc->sortKeysDesc()->each(
            function (Collection $item, $key) use (
                &$dataSets,
                &$clicksQuantityByGroupsColors,
                &$clicksSumByGroupsColors,
                $periodTemplate,
                $usdRate
            ) {
                $clicksQuantityByGroupsDataSetColor = array_shift($clicksQuantityByGroupsColors);

                $clicksQuantityByGroupsDataSet = [
                    'label' => $key,
                    'data' => $periodTemplate,
                    'backgroundColor' => $clicksQuantityByGroupsDataSetColor,
                    'borderColor' => $clicksQuantityByGroupsDataSetColor,
                    'fill' => false,
                    'type' => 'line',
                    'hidden' => true,
                ];

                $clicksSumByGroupsDataSetColor = array_shift($clicksSumByGroupsColors);

                $clicksSumByGroupsDataSet = [
                    'label' => $key,
                    'data' => $periodTemplate,
                    'backgroundColor' => $clicksSumByGroupsDataSetColor,
                    'borderColor' => $clicksSumByGroupsDataSetColor,
                    'fill' => false,
                    'type' => 'line',
                    'hidden' => true,
                ];

                $item->groupBy(
                    function (Collection $item) {
                        return Carbon::createFromFormat('Y-m-d', $item->get('Date'))->format('d-m');
                    }
                )
                    ->each(
                        function (Collection $item, $key) use (
                            &$clicksQuantityByGroupsDataSet,
                            &
                            $clicksSumByGroupsDataSet,
                            $usdRate
                        ) {
                            $clicksQuantityByGroupsDataSet['data'][$key] = $item->count();
                            $clicksSumByGroupsDataSet['data'][$key] = round(
                                $item->sum('Cost') * $usdRate / 100000,
                                2
                            );
                        }
                    );

                $clicksQuantityByGroupsDataSet['data'] = explode(
                    ',',
                    implode(',', $clicksQuantityByGroupsDataSet['data'])
                );
                $clicksSumByGroupsDataSet['data'] = explode(
                    ',',
                    implode(',', $clicksSumByGroupsDataSet['data'])
                );

                $dataSets['clicksQuantityByGroups'] = array_prepend(
                    $dataSets['clicksQuantityByGroups'],
                    $clicksQuantityByGroupsDataSet
                );
                $dataSets['clicksSumByGroups'] = array_prepend(
                    $dataSets['clicksSumByGroups'],
                    $clicksSumByGroupsDataSet
                );

            }
        );
        unset($acc);

        $graphs['clicksQuantityByGroups'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksQuantityByGroups'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksQuantityByGroups'] = json_encode($graphs['clicksQuantityByGroups']);

        $graphs['clicksSumByGroups'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksSumByGroups'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksSumByGroups'] = json_encode($graphs['clicksSumByGroups']);

        //endregion

        //region подготовка данных для графиков "клики по устройствам"

        $clicksQuantityByDevicesColors = $colors;
        $clicksSumByDevicesColors = $colors;

        $clicksQuantityByDevicesDataSetColor = array_shift($clicksQuantityByDevicesColors);
        $clicksQuantityByDevicesDataSetColor = self::hexToRGBA(
            $clicksQuantityByDevicesDataSetColor,
            0.2
        );

        $clicksQuantityByDevicesDataSet = [
            'label' => __('All'),
            'data' => $clicksQuantityByChannelsDataSet['data'],
            'backgroundColor' => $clicksQuantityByDevicesDataSetColor,
            'borderColor' => $clicksQuantityByDevicesDataSetColor,
            'fill' => false,
        ];

        $clicksSumByDevicesDataSetColor = array_shift($clicksSumByDevicesColors);
        $clicksSumByDevicesDataSetColor = self::hexToRGBA($clicksSumByDevicesDataSetColor, 0.2);

        $clicksSumByDevicesDataSet = [
            'label' => __('All'),
            'data' => $clicksSumByChannelsDataSet['data'],
            'backgroundColor' => $clicksSumByDevicesDataSetColor,
            'borderColor' => $clicksSumByDevicesDataSetColor,
            'fill' => false,
        ];

        $dataSets['clicksQuantityByDevices'] = array_prepend(
            $dataSets['clicksQuantityByDevices'],
            $clicksQuantityByDevicesDataSet
        );
        $dataSets['clicksSumByDevices'] = array_prepend(
            $dataSets['clicksSumByDevices'],
            $clicksSumByDevicesDataSet
        );


        $clicksAll
            ->groupBy(
                function (Collection $item) use ($devicesGroups) {

                    $deviceCategory = $item->get('DeviceCategory');

                    if (($deviceCategory ?? '') == '' || !isset($devicesGroups[$deviceCategory])) {
                        return __('-- No Device --');
                    }

                    return $devicesGroups[$deviceCategory];
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$clicksQuantityByDevicesColors,
                    &$clicksSumByDevicesColors,
                    $periodTemplate,
                    $usdRate
                ) {
                    $clicksQuantityByDevicesDataSetColor = array_shift($clicksQuantityByDevicesColors);

                    $clicksQuantityByDevicesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksQuantityByDevicesDataSetColor,
                        'borderColor' => $clicksQuantityByDevicesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $clicksSumByDevicesDataSetColor = array_shift($clicksSumByDevicesColors);

                    $clicksSumByDevicesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksSumByDevicesDataSetColor,
                        'borderColor' => $clicksSumByDevicesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromFormat('Y-m-d', $item->get('Date'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$clicksQuantityByDevicesDataSet,
                                &$clicksSumByDevicesDataSet,
                                $usdRate
                            ) {
                                $clicksQuantityByDevicesDataSet['data'][$key] = $item->count();
                                $clicksSumByDevicesDataSet['data'][$key] = round(
                                    $item->sum('Cost') * $usdRate / 100000,
                                    2
                                );
                            }
                        );

                    $clicksQuantityByDevicesDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksQuantityByDevicesDataSet['data'])
                    );
                    $clicksSumByDevicesDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksSumByDevicesDataSet['data'])
                    );

                    $dataSets['clicksQuantityByDevices'] = array_prepend(
                        $dataSets['clicksQuantityByDevices'],
                        $clicksQuantityByDevicesDataSet
                    );
                    $dataSets['clicksSumByDevices'] = array_prepend(
                        $dataSets['clicksSumByDevices'],
                        $clicksSumByDevicesDataSet
                    );

                }
            );


        $graphs['clicksQuantityByDevices'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksQuantityByDevices'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksQuantityByDevices'] = json_encode($graphs['clicksQuantityByDevices']);

        $graphs['clicksSumByDevices'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksSumByDevices'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksSumByDevices'] = json_encode($graphs['clicksSumByDevices']);

        //endregion

        //region подготовка данных для графиков "клики по возрасту"

        $clicksQuantityByAgesColors = $colors;
        $clicksSumByAgesColors = $colors;

        $clicksQuantityByAgesDataSetColor = array_shift($clicksQuantityByAgesColors);
        $clicksQuantityByAgesDataSetColor = self::hexToRGBA(
            $clicksQuantityByAgesDataSetColor,
            0.2
        );

        $clicksQuantityByAgesDataSet = [
            'label' => __('All'),
            'data' => $clicksQuantityByChannelsDataSet['data'],
            'backgroundColor' => $clicksQuantityByAgesDataSetColor,
            'borderColor' => $clicksQuantityByAgesDataSetColor,
            'fill' => false,
        ];

        $clicksSumByAgesDataSetColor = array_shift($clicksSumByAgesColors);
        $clicksSumByAgesDataSetColor = self::hexToRGBA($clicksSumByAgesDataSetColor, 0.2);

        $clicksSumByAgesDataSet = [
            'label' => __('All'),
            'data' => $clicksSumByChannelsDataSet['data'],
            'backgroundColor' => $clicksSumByAgesDataSetColor,
            'borderColor' => $clicksSumByAgesDataSetColor,
            'fill' => false,
        ];

        $dataSets['clicksQuantityByAges'] = array_prepend(
            $dataSets['clicksQuantityByAges'],
            $clicksQuantityByAgesDataSet
        );
        $dataSets['clicksSumByAges'] = array_prepend(
            $dataSets['clicksSumByAges'],
            $clicksSumByAgesDataSet
        );


        $clicksAll
            ->groupBy(
                function (Collection $item) use ($agesGroups) {

                    $ageInterval = $item->get('AgeInterval');

                    if (($ageInterval ?? '') == '' || !isset($agesGroups[$ageInterval])) {
                        return __('-- No Age --');
                    }

                    return $agesGroups[$ageInterval];
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$clicksQuantityByAgesColors,
                    &$clicksSumByAgesColors,
                    $periodTemplate,
                    $usdRate
                ) {
                    $clicksQuantityByAgesDataSetColor = array_shift($clicksQuantityByAgesColors);

                    $clicksQuantityByAgesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksQuantityByAgesDataSetColor,
                        'borderColor' => $clicksQuantityByAgesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $clicksSumByAgesDataSetColor = array_shift($clicksSumByAgesColors);

                    $clicksSumByAgesDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksSumByAgesDataSetColor,
                        'borderColor' => $clicksSumByAgesDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromFormat('Y-m-d', $item->get('Date'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$clicksQuantityByAgesDataSet,
                                &$clicksSumByAgesDataSet,
                                $usdRate
                            ) {
                                $clicksQuantityByAgesDataSet['data'][$key] = $item->count();
                                $clicksSumByAgesDataSet['data'][$key] = round(
                                    $item->sum('Cost') * $usdRate / 100000,
                                    2
                                );
                            }
                        );

                    $clicksQuantityByAgesDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksQuantityByAgesDataSet['data'])
                    );
                    $clicksSumByAgesDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksSumByAgesDataSet['data'])
                    );

                    $dataSets['clicksQuantityByAges'] = array_prepend(
                        $dataSets['clicksQuantityByAges'],
                        $clicksQuantityByAgesDataSet
                    );
                    $dataSets['clicksSumByAges'] = array_prepend(
                        $dataSets['clicksSumByAges'],
                        $clicksSumByAgesDataSet
                    );

                }
            );


        $graphs['clicksQuantityByAges'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksQuantityByAges'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksQuantityByAges'] = json_encode($graphs['clicksQuantityByAges']);

        $graphs['clicksSumByAges'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksSumByAges'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksSumByAges'] = json_encode($graphs['clicksSumByAges']);

        //endregion

        //region подготовка данных для графиков "клики по полу"

        $clicksQuantityByGendersColors = $colors;
        $clicksSumByGendersColors = $colors;

        $clicksQuantityByGendersDataSetColor = array_shift($clicksQuantityByGendersColors);
        $clicksQuantityByGendersDataSetColor = self::hexToRGBA(
            $clicksQuantityByGendersDataSetColor,
            0.2
        );

        $clicksQuantityByGendersDataSet = [
            'label' => __('All'),
            'data' => $clicksQuantityByChannelsDataSet['data'],
            'backgroundColor' => $clicksQuantityByGendersDataSetColor,
            'borderColor' => $clicksQuantityByGendersDataSetColor,
            'fill' => false,
        ];

        $clicksSumByGendersDataSetColor = array_shift($clicksSumByGendersColors);
        $clicksSumByGendersDataSetColor = self::hexToRGBA($clicksSumByGendersDataSetColor, 0.2);

        $clicksSumByGendersDataSet = [
            'label' => __('All'),
            'data' => $clicksSumByChannelsDataSet['data'],
            'backgroundColor' => $clicksSumByGendersDataSetColor,
            'borderColor' => $clicksSumByGendersDataSetColor,
            'fill' => false,
        ];

        $dataSets['clicksQuantityByGenders'] = array_prepend(
            $dataSets['clicksQuantityByGenders'],
            $clicksQuantityByGendersDataSet
        );
        $dataSets['clicksSumByGenders'] = array_prepend(
            $dataSets['clicksSumByGenders'],
            $clicksSumByGendersDataSet
        );


        $clicksAll
            ->groupBy(
                function (Collection $item) use ($gendersGroups) {

                    $gender = $item->get('Gender');

                    if (($gender ?? '') == '' || !isset($gendersGroups[$gender])) {
                        return __('-- No Gender--');
                    }

                    return $gendersGroups[$gender];
                }
            )
            ->each(
                function (Collection $item, $key) use (
                    &$dataSets,
                    &$clicksQuantityByGendersColors,
                    &$clicksSumByGendersColors,
                    $periodTemplate,
                    $usdRate
                ) {
                    $clicksQuantityByGendersDataSetColor = array_shift($clicksQuantityByGendersColors);

                    $clicksQuantityByGendersDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksQuantityByGendersDataSetColor,
                        'borderColor' => $clicksQuantityByGendersDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $clicksSumByGendersDataSetColor = array_shift($clicksSumByGendersColors);

                    $clicksSumByGendersDataSet = [
                        'label' => $key,
                        'data' => $periodTemplate,
                        'backgroundColor' => $clicksSumByGendersDataSetColor,
                        'borderColor' => $clicksSumByGendersDataSetColor,
                        'fill' => false,
                        'type' => 'line',
                    ];

                    $item->groupBy(
                        function (Collection $item) {
                            return Carbon::createFromFormat('Y-m-d', $item->get('Date'))->format('d-m');
                        }
                    )
                        ->each(
                            function (Collection $item, $key) use (
                                &$clicksQuantityByGendersDataSet,
                                &$clicksSumByGendersDataSet,
                                $usdRate
                            ) {
                                $clicksQuantityByGendersDataSet['data'][$key] = $item->count();
                                $clicksSumByGendersDataSet['data'][$key] = round(
                                    $item->sum('Cost') * $usdRate / 100000,
                                    2
                                );
                            }
                        );

                    $clicksQuantityByGendersDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksQuantityByGendersDataSet['data'])
                    );
                    $clicksSumByGendersDataSet['data'] = explode(
                        ',',
                        implode(',', $clicksSumByGendersDataSet['data'])
                    );

                    $dataSets['clicksQuantityByGenders'] = array_prepend(
                        $dataSets['clicksQuantityByGenders'],
                        $clicksQuantityByGendersDataSet
                    );
                    $dataSets['clicksSumByGenders'] = array_prepend(
                        $dataSets['clicksSumByGenders'],
                        $clicksSumByGendersDataSet
                    );

                }
            );


        $graphs['clicksQuantityByGenders'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksQuantityByGenders'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, PCs.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksQuantityByGenders'] = json_encode($graphs['clicksQuantityByGenders']);

        $graphs['clicksSumByGenders'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['clicksSumByGenders'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Clicks, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['clicksSumByGenders'] = json_encode($graphs['clicksSumByGenders']);

        //endregion


        //region подготовка данных для графиков "стоимость конверсий по источникам"

        $conversionsSumByChannelsColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByChannels'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByChannels = collect($dataSets['clicksSumByChannels'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByChannels,
                &
                $conversionsSumByChannelsColors
            ) {

                if (!$clicksSumByChannels->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByChannels->get($label);

                $conversionDataSet = [];

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round(
                        $sumDataSet['data'][$index] / $quantity,
                        2
                    );

                }

                $conversionsSumByChannelsDataSetColor = array_shift($conversionsSumByChannelsColors);

                $conversionsSumByChannelsDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByChannelsDataSetColor,
                    'borderColor' => $conversionsSumByChannelsDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumByChannels'] = array_prepend(
                    $dataSets['conversionsSumByChannels'],
                    $conversionsSumByChannelsDataSet
                );

                return true;
            }
        );


        $graphs['conversionsSumByChannels'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByChannels'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByChannels'] = json_encode($graphs['conversionsSumByChannels']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по UTM Source"

        $conversionsSumBySourcesColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessBySources'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumBySources = collect($dataSets['clicksSumBySources'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumBySources,
                &
                $conversionsSumBySourcesColors
            ) {

                if (!$clicksSumBySources->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumBySources->get($label);

                $conversionDataSet = [];

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round(
                        $sumDataSet['data'][$index] / $quantity,
                        2
                    );

                }

                $conversionsSumBySourcesDataSetColor = array_shift($conversionsSumBySourcesColors);

                $conversionsSumBySourcesDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumBySourcesDataSetColor,
                    'borderColor' => $conversionsSumBySourcesDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumBySources'] = array_prepend(
                    $dataSets['conversionsSumBySources'],
                    $conversionsSumBySourcesDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumBySources'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumBySources'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumBySources'] = json_encode($graphs['conversionsSumBySources']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по UTM Group"

        $conversionsSumByGroupsColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByGroups'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByGroups = collect($dataSets['clicksSumByGroups'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByGroups,
                &
                $conversionsSumByGroupsColors
            ) {

                if (!$clicksSumByGroups->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByGroups->get($label);

                $conversionDataSet = [];

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round(
                        $sumDataSet['data'][$index] / $quantity,
                        2
                    );

                }

                $conversionsSumByGroupsDataSetColor = array_shift($conversionsSumByGroupsColors);

                $conversionsSumByGroupsDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByGroupsDataSetColor,
                    'borderColor' => $conversionsSumByGroupsDataSetColor,
                    'fill' => false,
                    'hidden' => true,
                ];

                $dataSets['conversionsSumByGroups'] = array_prepend(
                    $dataSets['conversionsSumByGroups'],
                    $conversionsSumByGroupsDataSet
                );

                return true;
            }
        );


        $graphs['conversionsSumByGroups'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByGroups'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByGroups'] = json_encode($graphs['conversionsSumByGroups']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по устройствам"

        $conversionsSumByDevicesColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByDevices'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByDevices = collect($dataSets['clicksSumByDevices'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByDevices,
                &
                $conversionsSumByDevicesColors
            ) {

                if (!$clicksSumByDevices->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByDevices->get($label);

                $conversionDataSet = [];

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round(
                        $sumDataSet['data'][$index] / $quantity,
                        2
                    );

                }

                $conversionsSumByDevicesDataSetColor = array_shift($conversionsSumByDevicesColors);

                $conversionsSumByDevicesDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByDevicesDataSetColor,
                    'borderColor' => $conversionsSumByDevicesDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumByDevices'] = array_prepend(
                    $dataSets['conversionsSumByDevices'],
                    $conversionsSumByDevicesDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumByDevices'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByDevices'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByDevices'] = json_encode($graphs['conversionsSumByDevices']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по возрасту"

        $conversionsSumByAgesColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByAges'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByAges = collect($dataSets['clicksSumByAges'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (&$dataSets, $clicksSumByAges, &$conversionsSumByAgesColors) {

                if (!$clicksSumByAges->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByAges->get($label);

                $conversionDataSet = [];

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round(
                        $sumDataSet['data'][$index] / $quantity,
                        2
                    );

                }

                $conversionsSumByAgesDataSetColor = array_shift($conversionsSumByAgesColors);

                $conversionsSumByAgesDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByAgesDataSetColor,
                    'borderColor' => $conversionsSumByAgesDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumByAges'] = array_prepend(
                    $dataSets['conversionsSumByAges'],
                    $conversionsSumByAgesDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumByAges'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByAges'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByAges'] = json_encode($graphs['conversionsSumByAges']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по полу"

        $conversionsSumByGendersColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByGenders'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByGenders = collect($dataSets['clicksSumByGenders'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByGenders,
                &
                $conversionsSumByGendersColors
            ) {

                if (!$clicksSumByGenders->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByGenders->get($label);

                $conversionDataSet = [];

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round(
                        $sumDataSet['data'][$index] / $quantity,
                        2
                    );

                }

                $conversionsSumByGendersDataSetColor = array_shift($conversionsSumByGendersColors);

                $conversionsSumByGendersDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByGendersDataSetColor,
                    'borderColor' => $conversionsSumByGendersDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumByGenders'] = array_prepend(
                    $dataSets['conversionsSumByGenders'],
                    $conversionsSumByGendersDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumByGenders'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByGenders'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByGenders'] = json_encode($graphs['conversionsSumByGenders']);

        //endregion

        //region подготовка данных для графиков "затраты % по источникам"

        $costsPercentByChannelsColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByChannels'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByChannels = collect($dataSets['clicksSumByChannels'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByChannels,
                &
                $costsPercentByChannelsColors
            ) {

                if (!$clicksSumByChannels->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByChannels->get($label);

                $conversionDataSet = [];

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByChannelsDataSetColor = array_shift($costsPercentByChannelsColors);

                $costsPercentByChannelsDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByChannelsDataSetColor,
                    'borderColor' => $costsPercentByChannelsDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentByChannels'] = array_prepend(
                    $dataSets['costsPercentByChannels'],
                    $costsPercentByChannelsDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByChannels'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByChannels'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByChannels'] = json_encode($graphs['costsPercentByChannels']);

        //endregion

        //region подготовка данных для графиков "затраты % по UTM Source"

        $costsPercentBySourcesColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessBySources'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumBySources = collect($dataSets['clicksSumBySources'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumBySources,
                &
                $costsPercentBySourcesColors
            ) {

                if (!$clicksSumBySources->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumBySources->get($label);

                $conversionDataSet = [];

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentBySourcesDataSetColor = array_shift($costsPercentBySourcesColors);

                $costsPercentBySourcesDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentBySourcesDataSetColor,
                    'borderColor' => $costsPercentBySourcesDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentBySources'] = array_prepend(
                    $dataSets['costsPercentBySources'],
                    $costsPercentBySourcesDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentBySources'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentBySources'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentBySources'] = json_encode($graphs['costsPercentBySources']);

        //endregion

        //region подготовка данных для графиков "затраты % по UTM Group"

        $costsPercentByGroupsColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByGroups'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByGroups = collect($dataSets['clicksSumByGroups'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByGroups,
                &
                $costsPercentByGroupsColors
            ) {

                if (!$clicksSumByGroups->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByGroups->get($label);

                $conversionDataSet = [];

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByGroupsDataSetColor = array_shift($costsPercentByGroupsColors);

                $costsPercentByGroupsDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByGroupsDataSetColor,
                    'borderColor' => $costsPercentByGroupsDataSetColor,
                    'fill' => false,
                    'hidden' => true,
                ];

                $dataSets['costsPercentByGroups'] = array_prepend(
                    $dataSets['costsPercentByGroups'],
                    $costsPercentByGroupsDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByGroups'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByGroups'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByGroups'] = json_encode($graphs['costsPercentByGroups']);

        //endregion

        //region подготовка данных для графиков "затраты % по устройствам"

        $costsPercentByDevicesColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByDevices'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByDevices = collect($dataSets['clicksSumByDevices'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByDevices,
                &
                $costsPercentByDevicesColors
            ) {

                if (!$clicksSumByDevices->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByDevices->get($label);

                $conversionDataSet = [];

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByDevicesDataSetColor = array_shift($costsPercentByDevicesColors);

                $costsPercentByDevicesDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByDevicesDataSetColor,
                    'borderColor' => $costsPercentByDevicesDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentByDevices'] = array_prepend(
                    $dataSets['costsPercentByDevices'],
                    $costsPercentByDevicesDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByDevices'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByDevices'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByDevices'] = json_encode($graphs['costsPercentByDevices']);

        //endregion

        //region подготовка данных для графиков "затраты % по возрасту"

        $costsPercentByAgesColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByAges'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByAges = collect($dataSets['clicksSumByAges'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (&$dataSets, $clicksSumByAges, &$costsPercentByAgesColors) {

                if (!$clicksSumByAges->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByAges->get($label);

                $conversionDataSet = [];

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByAgesDataSetColor = array_shift($costsPercentByAgesColors);

                $costsPercentByAgesDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByAgesDataSetColor,
                    'borderColor' => $costsPercentByAgesDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentByAges'] = array_prepend(
                    $dataSets['costsPercentByAges'],
                    $costsPercentByAgesDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByAges'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByAges'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByAges'] = json_encode($graphs['costsPercentByAges']);

        //endregion

        //region подготовка данных для графиков "затраты % по полу"

        $costsPercentByGendersColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByGenders'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByGenders = collect($dataSets['clicksSumByGenders'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByGenders,
                &
                $costsPercentByGendersColors
            ) {

                if (!$clicksSumByGenders->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByGenders->get($label);

                $conversionDataSet = [];

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    $sum = $sumDataSet['data'][$index];

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByGendersDataSetColor = array_shift($costsPercentByGendersColors);

                $costsPercentByGendersDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByGendersDataSetColor,
                    'borderColor' => $costsPercentByGendersDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentByGenders'] = array_prepend(
                    $dataSets['costsPercentByGenders'],
                    $costsPercentByGendersDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByGenders'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByGenders'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByGenders'] = json_encode($graphs['costsPercentByGenders']);

        //endregion

        if ($n_average == 0) {
            goto render;
        }

        $dataSets = array_merge(
            $dataSets,
            [
                'conversionsSumByChannelsN' => [],
                'conversionsSumBySourcesN' => [],
                'conversionsSumByGroupsN' => [],
                'conversionsSumByDevicesN' => [],
                'conversionsSumByAgesN' => [],
                'conversionsSumByGendersN' => [],
                'costsPercentByChannelsN' => [],
                'costsPercentBySourcesN' => [],
                'costsPercentByGroupsN' => [],
                'costsPercentByDevicesN' => [],
                'costsPercentByAgesN' => [],
                'costsPercentByGendersN' => [],
            ]
        );

        $charts = array_merge(
            $charts,
            [
                'conversionsSumByChannelsN' => __('Conversions cost, rub.')." - ".__('by Channels')."[N]",
                'conversionsSumBySourcesN' => __('Conversions cost, rub.')." - ".__('by UTM Sources')."[N]",
                'conversionsSumByGroupsN' => __('Conversions cost, rub.')." - ".__('by UTM Groups')."[N]",
                'conversionsSumByDevicesN' => __('Conversions cost, rub.')." - ".__('by Devices')."[N]",
                'conversionsSumByAgesN' => __('Conversions cost, rub.')." - ".__('by Ages')."[N]",
                'conversionsSumByGendersN' => __('Conversions cost, rub.')." - ".__('by Genders')."[N]",
                'costsPercentByChannelsN' => __('Costs, %')." - ".__('by Channels')."[N]",
                'costsPercentBySourcesN' => __('Costs, %')." - ".__('by UTM Sources')."[N]",
                'costsPercentByGroupsN' => __('Costs, %')." - ".__('by UTM Groups')."[N]",
                'costsPercentByDevicesN' => __('Costs, %')." - ".__('by Devices')."[N]",
                'costsPercentByAgesN' => __('Costs, %')." - ".__('by Ages')."[N]",
                'costsPercentByGendersN' => __('Costs, %')." - ".__('by Genders')."[N]",
            ]
        );

        //region подготовка данных для графиков "стоимость конверсий по источникам n-среднее"

        $conversionsSumByChannelsNColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByChannels'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByChannels = collect($dataSets['clicksSumByChannels'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByChannels,
                &
                $conversionsSumByChannelsNColors,
                $n_average
            ) {

                if (!$clicksSumByChannels->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByChannels->get($label);

                $conversionDataSet = [];

                $countDataSet = count($quantityDataSet['data']);

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $quantity = array_sum(
                            array_slice($quantityDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round($sum / $quantity, 2);

                }

                $conversionsSumByChannelsNDataSetColor = array_shift($conversionsSumByChannelsNColors);

                $conversionsSumByChannelsNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByChannelsNDataSetColor,
                    'borderColor' => $conversionsSumByChannelsNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumByChannelsN'] = array_prepend(
                    $dataSets['conversionsSumByChannelsN'],
                    $conversionsSumByChannelsNDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumByChannelsN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByChannelsN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByChannelsN'] = json_encode($graphs['conversionsSumByChannelsN']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по UTM Source n-среднее"

        $conversionsSumBySourcesNColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessBySources'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumBySources = collect($dataSets['clicksSumBySources'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumBySources,
                &
                $conversionsSumBySourcesNColors,
                $n_average
            ) {

                if (!$clicksSumBySources->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumBySources->get($label);

                $conversionDataSet = [];

                $countDataSet = count($quantityDataSet['data']);

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $quantity = array_sum(
                            array_slice($quantityDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round($sum / $quantity, 2);

                }

                $conversionsSumBySourcesNDataSetColor = array_shift($conversionsSumBySourcesNColors);

                $conversionsSumBySourcesNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumBySourcesNDataSetColor,
                    'borderColor' => $conversionsSumBySourcesNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumBySourcesN'] = array_prepend(
                    $dataSets['conversionsSumBySourcesN'],
                    $conversionsSumBySourcesNDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumBySourcesN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumBySourcesN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumBySourcesN'] = json_encode($graphs['conversionsSumBySourcesN']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по UTM Group n-среднее"

        $conversionsSumByGroupsNColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByGroups'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByGroups = collect($dataSets['clicksSumByGroups'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByGroups,
                &$conversionsSumByGroupsNColors,
                $n_average
            ) {

                if (!$clicksSumByGroups->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByGroups->get($label);

                $conversionDataSet = [];

                $countDataSet = count($quantityDataSet['data']);

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $quantity = array_sum(
                            array_slice($quantityDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round($sum / $quantity, 2);

                }

                $conversionsSumByGroupsNDataSetColor = array_shift($conversionsSumByGroupsNColors);

                $conversionsSumByGroupsNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByGroupsNDataSetColor,
                    'borderColor' => $conversionsSumByGroupsNDataSetColor,
                    'fill' => false,
                    'hidden' => true,
                ];

                $dataSets['conversionsSumByGroupsN'] = array_prepend(
                    $dataSets['conversionsSumByGroupsN'],
                    $conversionsSumByGroupsNDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumByGroupsN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByGroupsN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByGroupsN'] = json_encode($graphs['conversionsSumByGroupsN']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по устройствам n-среднее"

        $conversionsSumByDevicesNColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByDevices'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByDevices = collect($dataSets['clicksSumByDevices'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByDevices,
                &$conversionsSumByDevicesNColors,
                $n_average
            ) {

                if (!$clicksSumByDevices->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByDevices->get($label);

                $conversionDataSet = [];

                $countDataSet = count($quantityDataSet['data']);

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $quantity = array_sum(
                            array_slice($quantityDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round($sum / $quantity, 2);

                }

                $conversionsSumByDevicesNDataSetColor = array_shift($conversionsSumByDevicesNColors);

                $conversionsSumByDevicesNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByDevicesNDataSetColor,
                    'borderColor' => $conversionsSumByDevicesNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumByDevicesN'] = array_prepend(
                    $dataSets['conversionsSumByDevicesN'],
                    $conversionsSumByDevicesNDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumByDevicesN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByDevicesN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByDevicesN'] = json_encode($graphs['conversionsSumByDevicesN']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по возрасту n-среднее"

        $conversionsSumByAgesNColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByAges'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByAges = collect($dataSets['clicksSumByAges'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByAges,
                &$conversionsSumByAgesNColors,
                $n_average
            ) {

                if (!$clicksSumByAges->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByAges->get($label);

                $conversionDataSet = [];

                $countDataSet = count($quantityDataSet['data']);

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $quantity = array_sum(
                            array_slice($quantityDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round($sum / $quantity, 2);

                }

                $conversionsSumByAgesNDataSetColor = array_shift($conversionsSumByAgesNColors);

                $conversionsSumByAgesNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByAgesNDataSetColor,
                    'borderColor' => $conversionsSumByAgesNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumByAgesN'] = array_prepend(
                    $dataSets['conversionsSumByAgesN'],
                    $conversionsSumByAgesNDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumByAgesN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByAgesN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByAgesN'] = json_encode($graphs['conversionsSumByAgesN']);

        //endregion

        //region подготовка данных для графиков "стоимость конверсий по полу n-среднее"

        $conversionsSumByGendersNColors = $colors;

        $ordersQuantitySuccessByChannel = collect($dataSets['ordersQuantitySuccessByGenders'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByGenders = collect($dataSets['clicksSumByGenders'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersQuantitySuccessByChannel->each(
            function (array $quantityDataSet, $label) use (
                &$dataSets,
                $clicksSumByGenders,
                &$conversionsSumByGendersNColors,
                $n_average
            ) {

                if (!$clicksSumByGenders->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByGenders->get($label);

                $conversionDataSet = [];

                $countDataSet = count($quantityDataSet['data']);

                foreach ($quantityDataSet['data'] as $index => $quantity) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $quantity = array_sum(
                            array_slice($quantityDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $quantity < 1 ? ($sum > 0 ? -1000 : 0) : round($sum / $quantity, 2);

                }

                $conversionsSumByGendersNDataSetColor = array_shift($conversionsSumByGendersNColors);

                $conversionsSumByGendersNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $conversionsSumByGendersNDataSetColor,
                    'borderColor' => $conversionsSumByGendersNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['conversionsSumByGendersN'] = array_prepend(
                    $dataSets['conversionsSumByGendersN'],
                    $conversionsSumByGendersNDataSet
                );

                return true;

            }
        );


        $graphs['conversionsSumByGendersN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['conversionsSumByGendersN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Conversions cost, rub.'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['conversionsSumByGendersN'] = json_encode($graphs['conversionsSumByGendersN']);

        //endregion

        //region подготовка данных для графиков "затраты % по источникам"

        $costsPercentByChannelsNColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByChannels'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByChannels = collect($dataSets['clicksSumByChannels'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByChannels,
                &
                $costsPercentByChannelsNColors,
                $n_average
            ) {

                if (!$clicksSumByChannels->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByChannels->get($label);

                $conversionDataSet = [];

                $countDataSet = count($sumOrdersDataSet['data']);

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $sumOrders = array_sum(
                            array_slice($sumOrdersDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByChannelsNDataSetColor = array_shift($costsPercentByChannelsNColors);

                $costsPercentByChannelsNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByChannelsNDataSetColor,
                    'borderColor' => $costsPercentByChannelsNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentByChannelsN'] = array_prepend(
                    $dataSets['costsPercentByChannelsN'],
                    $costsPercentByChannelsNDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByChannelsN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByChannelsN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByChannelsN'] = json_encode($graphs['costsPercentByChannelsN']);

        //endregion

        //region подготовка данных для графиков "затраты % по UTM Source"

        $costsPercentBySourcesNColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessBySources'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumBySources = collect($dataSets['clicksSumBySources'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumBySources,
                &
                $costsPercentBySourcesNColors,
                $n_average
            ) {

                if (!$clicksSumBySources->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumBySources->get($label);

                $conversionDataSet = [];

                $countDataSet = count($sumOrdersDataSet['data']);

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $sumOrders = array_sum(
                            array_slice($sumOrdersDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }
                $costsPercentBySourcesNDataSetColor = array_shift($costsPercentBySourcesNColors);

                $costsPercentBySourcesNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentBySourcesNDataSetColor,
                    'borderColor' => $costsPercentBySourcesNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentBySourcesN'] = array_prepend(
                    $dataSets['costsPercentBySourcesN'],
                    $costsPercentBySourcesNDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentBySourcesN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentBySourcesN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentBySourcesN'] = json_encode($graphs['costsPercentBySourcesN']);

        //endregion

        //region подготовка данных для графиков "затраты % по UTM Group"

        $costsPercentByGroupsNColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByGroups'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByGroups = collect($dataSets['clicksSumByGroups'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByGroups,
                &
                $costsPercentByGroupsNColors,
                $n_average
            ) {

                if (!$clicksSumByGroups->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByGroups->get($label);

                $conversionDataSet = [];

                $countDataSet = count($sumOrdersDataSet['data']);

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $sumOrders = array_sum(
                            array_slice($sumOrdersDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByGroupsNDataSetColor = array_shift($costsPercentByGroupsNColors);

                $costsPercentByGroupsNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByGroupsNDataSetColor,
                    'borderColor' => $costsPercentByGroupsNDataSetColor,
                    'fill' => false,
                    'hidden' => true,
                ];

                $dataSets['costsPercentByGroupsN'] = array_prepend(
                    $dataSets['costsPercentByGroupsN'],
                    $costsPercentByGroupsNDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByGroupsN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByGroupsN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByGroupsN'] = json_encode($graphs['costsPercentByGroupsN']);

        //endregion

        //region подготовка данных для графиков "затраты % по устройствам"

        $costsPercentByDevicesNColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByDevices'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByDevices = collect($dataSets['clicksSumByDevices'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByDevices,
                &
                $costsPercentByDevicesNColors,
                $n_average
            ) {

                if (!$clicksSumByDevices->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByDevices->get($label);

                $conversionDataSet = [];

                $countDataSet = count($sumOrdersDataSet['data']);

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $sumOrders = array_sum(
                            array_slice($sumOrdersDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByDevicesNDataSetColor = array_shift($costsPercentByDevicesNColors);

                $costsPercentByDevicesNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByDevicesNDataSetColor,
                    'borderColor' => $costsPercentByDevicesNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentByDevicesN'] = array_prepend(
                    $dataSets['costsPercentByDevicesN'],
                    $costsPercentByDevicesNDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByDevicesN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByDevicesN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByDevicesN'] = json_encode($graphs['costsPercentByDevicesN']);

        //endregion

        //region подготовка данных для графиков "затраты % по возрасту"

        $costsPercentByAgesNColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByAges'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByAges = collect($dataSets['clicksSumByAges'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByAges,
                &$costsPercentByAgesNColors,
                $n_average
            ) {

                if (!$clicksSumByAges->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByAges->get($label);

                $conversionDataSet = [];

                $countDataSet = count($sumOrdersDataSet['data']);

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $sumOrders = array_sum(
                            array_slice($sumOrdersDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByAgesNDataSetColor = array_shift($costsPercentByAgesNColors);

                $costsPercentByAgesNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByAgesNDataSetColor,
                    'borderColor' => $costsPercentByAgesNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentByAgesN'] = array_prepend(
                    $dataSets['costsPercentByAgesN'],
                    $costsPercentByAgesNDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByAgesN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByAgesN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByAgesN'] = json_encode($graphs['costsPercentByAgesN']);

        //endregion

        //region подготовка данных для графиков "затраты % по полу"

        $costsPercentByGendersNColors = $colors;

        $ordersSumSuccessByChannel = collect($dataSets['ordersSumSuccessByGenders'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );

        $clicksSumByGenders = collect($dataSets['clicksSumByGenders'])
            ->keyBy(
                function (array $item) {
                    return $item['label'];
                }
            );


        $ordersSumSuccessByChannel->each(
            function (array $sumOrdersDataSet, $label) use (
                &$dataSets,
                $clicksSumByGenders,
                &
                $costsPercentByGendersNColors,
                $n_average
            ) {

                if (!$clicksSumByGenders->has($label)) {
                    return true;
                }

                $sumDataSet = $clicksSumByGenders->get($label);

                $conversionDataSet = [];

                $countDataSet = count($sumOrdersDataSet['data']);

                foreach ($sumOrdersDataSet['data'] as $index => $sumOrders) {

                    if ($index >= $n_average && $index < ($countDataSet - $n_average)) {
                        $sumOrders = array_sum(
                            array_slice($sumOrdersDataSet['data'], ($index - $n_average), $n_average * 2 + 1)
                        );
                        $sum = array_sum(array_slice($sumDataSet['data'], ($index - $n_average), $n_average * 2 + 1));
                    } else {
                        $sum = $sumDataSet['data'][$index];
                    }

                    $conversionDataSet[] = $sumOrders < 1 ? ($sum > 0 ? -10 : 0) : round(
                        $sum / $sumOrders * 100,
                        2
                    );

                }

                $costsPercentByGendersNDataSetColor = array_shift($costsPercentByGendersNColors);

                $costsPercentByGendersNDataSet = [
                    'label' => $label,
                    'data' => $conversionDataSet,
                    'backgroundColor' => $costsPercentByGendersNDataSetColor,
                    'borderColor' => $costsPercentByGendersNDataSetColor,
                    'fill' => false,
                ];

                $dataSets['costsPercentByGendersN'] = array_prepend(
                    $dataSets['costsPercentByGendersN'],
                    $costsPercentByGendersNDataSet
                );

                return true;

            }
        );


        $graphs['costsPercentByGendersN'] = [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($periodTemplate),
                'datasets' => $dataSets['costsPercentByGendersN'],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => __('Costs, %'),
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ],
                'aspectRatio' => 2.5,
            ],
        ];

        $graphs['costsPercentByGendersN'] = json_encode($graphs['costsPercentByGendersN']);

        //endregion

        render:

        $utmCampaigns = collect();
        $utmSources = collect();

        Channel::each(
            function (Channel $channel) use (&$utmCampaigns, &$utmSources, $db) {
                if (!($channel->yandex_counter ?? 0)) {
                    return true;
                }

                $dbName = "db_{$channel->yandex_counter}_ya_counter";

                if (!$db->isDatabaseExist($dbName)) {
                    return true;
                }

                if (!$db->isExists($dbName, 'hits')) {
                    return true;
                }

                $channelUTMs = $db->select(
                    "SELECT DISTINCT UTMCampaign, UTMSource FROM {$dbName}.hits"
                )->rows();

                if (empty($channelUTMs)) {
                    return true;
                }

                foreach ($channelUTMs as $channelUTM) {
                    if ($channelUTM['UTMCampaign'] == '') {
                        $channelUTM['UTMCampaign'] = __('-- No UTM --');
                    } elseif (preg_match('/[\{\}&\?\=]+/', $channelUTM['UTMCampaign'])) {
                        $channelUTM['UTMCampaign'] = __('-- Error UTM --');
                    }
                    $utmCampaigns->put($channelUTM['UTMCampaign'], $channelUTM['UTMCampaign']);

                    if ($channelUTM['UTMSource'] == '') {
                        $channelUTM['UTMSource'] = __('-- No UTM --');
                    } elseif (preg_match('/[\{\}&\?\=]+/', $channelUTM['UTMSource'])) {
                        $channelUTM['UTMSource'] = __('-- Error UTM --');
                    }
                    $utmSources->put($channelUTM['UTMSource'], $channelUTM['UTMSource']);
                }

                return true;
            }
        );

        $utmCampaigns = $utmCampaigns->unique()->sort()->toArray();
        $utmSources = $utmSources->unique()->sort()->toArray();

        $orderDetailStates = OrderDetailState::pluck('name', 'id');
        $utmGroups = UtmGroup::pluck('name', 'id');

        return view(
            'analytics.reports.ads.graph.by.channels',
            compact(
                'dateFrom',
                'dateTo',
                'orderDetailStates',
                'utmCampaigns',
                'utmSources',
                'utmGroups',
                'charts',
                'devicesGroups',
                'agesGroups',
                'gendersGroups',
                'successful_states',
                'minimal_states',
                'utm_campaigns',
                'utm_sources',
                'utm_groups',
                'chart_selected',
                'devices',
                'ages',
                'genders',
                'graphs',
                'n_average',
                'multiChartFirst',
                'multiChartSecond'
            )
        );
    }

    /**
     * Конвертирует цвет HEX в RGBA
     *
     * @param string $color
     * @param float $alfa
     * @return string
     */
    protected static function hexToRGBA(string $color, float $alfa = 1): string
    {
        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");

        return "rgba({$r},{$g},{$b}, {$alfa})";
    }

    /**
     * Рекурсивный обход массива и преобразование его в формат Объект-Массива для JSON
     *
     * @param mixed $item
     * @return mixed $item
     */
    protected static function handleJSONArray(&$item)
    {
        if (is_array($item)) {
            foreach ($item as &$value) {
                self::handleJSONArray($value);
            }

            $keys = array_keys($item);

            $item = !empty($keys) && !is_numeric(array_shift($keys)) ? (object)$item : $item;
        }

        return $item;
    }

    /**
     * Отображение отчета по доставкам отправлений СДЭК
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function reportCdekDelivery(Request $request)
    {
        $data = [
            'groups' => [],
            'total_rows' => [],
        ];

        $statusData = [];

        if ($request->report) {
            if ($request->submit && $request->from && $request->to) {
                $dataFrom = Carbon::createFromFormat('d-m-Y', $request->from)->setTime(0, 0, 0, 0);
                $dataTo = Carbon::createFromFormat('d-m-Y', $request->to)->setTime(23, 59, 59, 0);

                $carriers = Carrier::all()->reduce(
                    function ($carry, Carrier $carrier) {
                        $carrierConfig = $carrier->getConfigVars();
                        if ($carrierConfig->get('operator') == 'cdek') {
                            $carrierName = [];
                            preg_match('/\[([^\[\]]*)\]/', $carrier->name, $carrierName);
                            $carrierName = isset($carrierName[1]) ? $carrierName[1] : $carrier->name;
                            $carry[$carrierName] = [
                                'account' => $carrierConfig->get('operator_account'),
                                'secure' => $carrierConfig->get('operator_secure'),
                                'carrierObject' => $carrier,
                            ];
                        }

                        return $carry;
                    },
                    []
                );

                $resultByCarriers = collect([]);
                foreach ($carriers as $carrierName => $carrier) {
                    if (isset($carrier['account']) && isset($carrier['secure'])) {
                        $carrierResult = $this->getCdekDelivery(
                            $carrier['account'],
                            $carrier['secure'],
                            $dataFrom,
                            $dataTo
                        );
                        $carrierResult = collect($carrierResult)->filter(
                            function (array $item) use (
                                $dataFrom,
                                $dataTo
                            ) {
                                $currentTimestamp = Carbon::createFromFormat('Y-m-d', $item['Date'])->timestamp;

                                return $currentTimestamp >= $dataFrom->timestamp && $currentTimestamp <= $dataTo->timestamp;
                            }
                        );

                        $carrierResult->transform(
                            function (array $data, $dispatchNumber) {
                                $order = Order::query()
                                    ->where('delivery_shipping_number', (string)$dispatchNumber)
                                    ->first();

                                if ($order instanceof Order) {
                                    $orderDetails = $order->orderDetails->reduce(
                                        function (array $carry, OrderDetail $orderDetail) {

                                            if ($orderDetail->product->isNeedGuarantee()) {
                                                $carry['Guarantee'][] = $orderDetail;
                                            } else {
                                                $carry['NoGuarantee'][] = $orderDetail;
                                            }

                                            return $carry;
                                        },
                                        [
                                            'Guarantee' => [],
                                            'NoGuarantee' => [],
                                        ]
                                    );
                                }

                                $orderDetails['Guarantee'] = $orderDetails['Guarantee'] ?? [];
                                $orderDetails['NoGuarantee'] = $orderDetails['NoGuarantee'] ?? [];

                                $orderDetails = !empty($orderDetails['Guarantee']) ? $orderDetails['Guarantee'] : $orderDetails['NoGuarantee'];

                                $brand = __('Unknown');
                                $expectedSum = 0;

                                if (!empty($orderDetails)) {
                                    /**
                                     * @var OrderDetail $orderDetail
                                     */
                                    $orderDetail = collect($orderDetails)->sortBy('price')->first();
                                    $brand = $orderDetail->product->manufacturer->name;
                                    $expectedSum = round($orderDetail->price * $orderDetail->currency->currency_rate, 2);
                                }

                                $data['CashOnDelivFactRetail'] = $data['CashOnDelivFact'] > 30000 ? 0 : $data['CashOnDelivFact'];

                                $data['Brand'] = $brand;
                                $data['ExpectedSum'] = $data['CashOnDelivFact'] > 30000 ? 0 : $expectedSum;
                                $data['dispatchNumber'] = $dispatchNumber;
                                $data['RetailAllQuantity'] = $data['CashOnDelivFact'] <= 30000 && $data['CashOnDeliv'] > 0 ? 1 : 0;
                                $data['RetailFactQuantity'] = $data['CashOnDelivFact'] > 0 && $data['CashOnDelivFact'] <= 30000 ? 1 : 0;

                                return $data;
                            }
                        );


                        $resultByDate = $carrierResult->groupBy('Date')
                            ->map(
                                function (Collection $group, $key) {
                                    return collect(
                                        [
                                            'Date' => $key,
                                            'CashOnDeliv' => $group->sum('CashOnDeliv'),
                                            'CashOnDelivFact' => $group->sum('CashOnDelivFact'),
                                            'PackageDeliverySum' => $group->sum('PackageDeliverySum'),
                                            'PackageServiceSum' => $group->sum('PackageServiceSum'),
                                            'Cash' => ($group->sum('CashOnDelivFact') - $group->sum(
                                                    'PackageDeliverySum'
                                                ) - $group->sum('PackageServiceSum')),
                                            'CashOnDelivFactRetail' => $group->where('RetailFactQuantity', '>', 0)->sum(
                                                'CashOnDelivFactRetail'
                                            ),
                                            'CashOnDelivFactRetailPVZ' => $group->where('PVZ', 1)->where(
                                                'RetailFactQuantity',
                                                '>',
                                                0
                                            )->sum('CashOnDelivFactRetail'),
                                            'CashOnDelivFactRetailCourier' => $group->where('PVZ', 0)->where(
                                                'RetailFactQuantity',
                                                '>',
                                                0
                                            )->sum('CashOnDelivFactRetail'),
                                            'ExpectedSum' => $group->where('RetailAllQuantity', '>', 0)->unique(
                                                'dispatchNumber'
                                            )->sum('ExpectedSum'),
                                            'ExpectedSumPVZ' => $group->where('PVZ', 1)->where(
                                                'RetailAllQuantity',
                                                '>',
                                                0
                                            )->unique('dispatchNumber')->sum('ExpectedSum'),
                                            'ExpectedSumCourier' => $group->where('PVZ', 0)->where(
                                                'RetailAllQuantity',
                                                '>',
                                                0
                                            )->unique('dispatchNumber')->sum('ExpectedSum'),
                                            'RetailAllQuantity' => $group->where('RetailAllQuantity', '>', 0)->unique(
                                                'dispatchNumber'
                                            )->count(),
                                            'RetailAllQuantityPVZ' => $group->where('PVZ', 1)->where(
                                                'RetailAllQuantity',
                                                '>',
                                                0
                                            )->unique('dispatchNumber')->count(),
                                            'RetailAllQuantityCourier' => $group->where('PVZ', 0)->where(
                                                'RetailAllQuantity',
                                                '>',
                                                0
                                            )->unique('dispatchNumber')->count(),
                                            'RetailFactQuantity' => $group->where('RetailFactQuantity', '>', 0)->unique(
                                                'dispatchNumber'
                                            )->count(),
                                            'RetailFactQuantityPVZ' => $group->where('PVZ', 1)->where(
                                                'RetailFactQuantity',
                                                '>',
                                                0
                                            )->unique('dispatchNumber')->count(),
                                            'RetailFactQuantityCourier' => $group->where('PVZ', 0)->where(
                                                'RetailFactQuantity',
                                                '>',
                                                0
                                            )->unique('dispatchNumber')->count(),
                                        ]
                                    );
                                }
                            );

                        /**
                         * @var Collection $resultByDate
                         */
                        $resultByDate = $resultByDate->sortBy('Date');

                        $totalResult = collect(
                            [
                                'CashOnDeliv' => $resultByDate->sum('CashOnDeliv'),
                                'CashOnDelivFact' => $resultByDate->sum('CashOnDelivFact'),
                                'PackageDeliverySum' => $resultByDate->sum('PackageDeliverySum'),
                                'PackageServiceSum' => $resultByDate->sum('PackageServiceSum'),
                                'Cash' => ($resultByDate->sum('CashOnDelivFact') - $resultByDate->sum(
                                        'PackageDeliverySum'
                                    ) - $resultByDate->sum('PackageServiceSum')),
                                'CashOnDelivFactRetail' => $resultByDate->sum('CashOnDelivFactRetail'),
                                'CashOnDelivFactRetailPVZ' => $resultByDate->sum('CashOnDelivFactRetailPVZ'),
                                'CashOnDelivFactRetailCourier' => $resultByDate->sum('CashOnDelivFactRetailCourier'),
                                'ExpectedSum' => $resultByDate->sum('ExpectedSum'),
                                'ExpectedSumPVZ' => $resultByDate->sum('ExpectedSumPVZ'),
                                'ExpectedSumCourier' => $resultByDate->sum('ExpectedSumCourier'),
                                'RetailAllQuantity' => $resultByDate->sum('RetailAllQuantity'),
                                'RetailAllQuantityPVZ' => $resultByDate->sum('RetailAllQuantityPVZ'),
                                'RetailAllQuantityCourier' => $resultByDate->sum('RetailAllQuantityCourier'),
                                'RetailFactQuantity' => $resultByDate->sum('RetailFactQuantity'),
                                'RetailFactQuantityPVZ' => $resultByDate->sum('RetailFactQuantityPVZ'),
                                'RetailFactQuantityCourier' => $resultByDate->sum('RetailFactQuantityCourier'),
                            ]
                        );

                        foreach ($resultByDate->toArray() as $row) {
                            $data['groups'][$carrierName]['rows'][$row['Date']] = $row;
                        }

                        $data['groups'][$carrierName]['total'][__('Total')] = $totalResult->toArray();
                        $resultByCarriers->push($totalResult->toArray());

                        $carrierResult
                            ->where('CashOnDelivFact', '<=', 30000)
                            ->where('CashOnDeliv', '>', 0)
                            ->sortBy('StatusDescription')->groupBy('StatusDescription')->map(
                                function (Collection $group, $statusDescription) use (&$statusData, $carrierName) {
                                    $statusDescription = !empty($statusDescription) ? $statusDescription : '--';
                                    $group->sortBy('ReasonDescription')->groupBy('ReasonDescription')->map(
                                        function (Collection $group, $reasonDescription) use (
                                            &$statusData,
                                            $carrierName,
                                            $statusDescription
                                        ) {
                                            $reasonDescription = !empty($reasonDescription) ? $reasonDescription : '--';
                                            $statusData[$carrierName][$statusDescription][$reasonDescription]['quantity'] = $group->count(
                                            );
                                            foreach ($group->toArray() as $order) {
                                                $statusData[$carrierName][$statusDescription][$reasonDescription]['orders'][] = $order['OrderNumber'];
                                            }
                                        }
                                    );

                                    arsort($statusData[$carrierName][$statusDescription]);
                                }
                            );

                    }
                }

                $totalResultAll = collect(
                    [
                        'CashOnDeliv' => $resultByCarriers->sum('CashOnDeliv'),
                        'CashOnDelivFact' => $resultByCarriers->sum('CashOnDelivFact'),
                        'PackageDeliverySum' => $resultByCarriers->sum('PackageDeliverySum'),
                        'PackageServiceSum' => $resultByCarriers->sum('PackageServiceSum'),
                        'Cash' => ($resultByCarriers->sum('CashOnDelivFact') - $resultByCarriers->sum(
                                'PackageDeliverySum'
                            ) - $resultByCarriers->sum('PackageServiceSum')),
                        'CashOnDelivFactRetail' => $resultByCarriers->sum('CashOnDelivFactRetail'),
                        'CashOnDelivFactRetailPVZ' => $resultByCarriers->sum('CashOnDelivFactRetailPVZ'),
                        'CashOnDelivFactRetailCourier' => $resultByCarriers->sum('CashOnDelivFactRetailCourier'),
                        'ExpectedSum' => $resultByCarriers->sum('ExpectedSum'),
                        'ExpectedSumPVZ' => $resultByCarriers->sum('ExpectedSumPVZ'),
                        'ExpectedSumCourier' => $resultByCarriers->sum('ExpectedSumCourier'),
                        'RetailAllQuantity' => $resultByCarriers->sum('RetailAllQuantity'),
                        'RetailAllQuantityPVZ' => $resultByCarriers->sum('RetailAllQuantityPVZ'),
                        'RetailAllQuantityCourier' => $resultByCarriers->sum('RetailAllQuantityCourier'),
                        'RetailFactQuantity' => $resultByCarriers->sum('RetailFactQuantity'),
                        'RetailFactQuantityPVZ' => $resultByCarriers->sum('RetailFactQuantityPVZ'),
                        'RetailFactQuantityCourier' => $resultByCarriers->sum('RetailFactQuantityCourier'),
                    ]
                );

                $data['total_rows'][__('Total for all')] = $totalResultAll->toArray();

            }
            if ($request->save) {
                Configuration::updateOrCreate(
                    ['name' => 'reportCdekDelivery_user_'.\Auth::user()->getAuthIdentifier()],
                    [
                        'values' => json_encode(
                            [
                                'dateFrom' => is_null($request->from) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->from)->setTime(0, 0, 0, 0),
                                    false
                                ),
                                'dateTo' => is_null($request->to) ? null : Carbon::now()->setTime(
                                    0,
                                    0,
                                    0,
                                    0
                                )->diffInDays(
                                    Carbon::createFromFormat('d-m-Y', $request->to)->setTime(0, 0, 0, 0),
                                    false
                                ),
                            ]
                        ),
                    ]
                );
            }
            $dateFrom = $request->from;
            $dateTo = $request->to;
        } else {
            $configuration = Configuration::all()->where(
                'name',
                'reportCdekDelivery_user_'.\Auth::user()->getAuthIdentifier()
            )->first();
            $values = $configuration ? json_decode($configuration->values) : [];
            $dateFrom = is_object($values) && isset($values->dateFrom) && !is_null($values->dateFrom) ? Carbon::now(
            )->addDays($values->dateFrom)->format('d-m-Y') : null;
            $dateTo = is_object($values) && isset($values->dateTo) && !is_null($values->dateTo) ? Carbon::now(
            )->addDays($values->dateTo)->format('d-m-Y') : null;
        }

        return view(
            'analytics.reports.cdek.delivery',
            compact('data', 'statusData', 'dateFrom', 'dateTo')
        );
    }

    /**
     * Отображение отчета по доставке
     *
     * @param ReportRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reportDeliveryMain(ReportRequest $request)
    {
        /**
         * @var \Illuminate\Support\Carbon|null $from
         * @var \Illuminate\Support\Carbon|null $to
         */
        $from = $request->from;
        $to = $request->to;

        if (!$request->submit) {
            goto renderView;
        }

        $orders = Order::query()->where('is_hidden', '0');

        if (!is_null($from)) {
            $orders = $orders->where('created_at', '>=', $from->toDateTimeString());
        }

        if (!is_null($to)) {
            $orders = $orders->where('created_at', '<=', $to->toDateTimeString());
        }

        $orders = $orders->orderBy('created_at')->get();


        if ($orders->count() < 1) {
            goto renderView;
        }

        //region Подготовка шаблона отчета

        /**
         * @var \Illuminate\Support\Carbon $dateFrom
         * @var \Illuminate\Support\Carbon $dateTo
         */
        $dateFrom = Carbon::createFromTimestamp(
            ($from ?? $orders->first()->created_at)->setTime(0, 0, 0, 0)->getTimestamp()
        );
        $dateTo = ($to ?? $orders->last()->created_at)->setTime(23, 59, 59, 999999);

        $channelsGroupsTemplate = collect(
            Channel::all()->groupBy('name')->reduce(
                function (array $carry, Collection $group) {
                    $carry[$group->first()->name] = [];

                    return $carry;
                },
                []
            )
        )->sortKeys()->toArray();

        $brandsGroupsTemplate = array_merge(
            collect(
                Manufacturer::all()->groupBy('name')->reduce(
                    function (array $carry, Collection $group) {
                        $carry[$group->first()->name] = [];

                        return $carry;
                    },
                    []
                )
            )->sortKeys()->toArray(),
            [__('Unknown') => []]
        );

        $carriersGroupsTemplate = array_merge(
            collect(
                Carrier::all()->groupBy('name')->reduce(
                    function (array $carry, Collection $group) {
                        /**
                         * @var Carrier $carrier
                         */
                        $carrier = $group->first();
                        $carrierConfig = $carrier->getConfigVars();

                        $carrierName = [];
                        preg_match('/\[([^\[\]]*)\]/', $carrier->name, $carrierName);
                        $carrierName = isset($carrierName[1]) ? $carrierName[1] : $carrier->name;

                        $carry[$carrierName] = [];

                        if ($carrierConfig->has('type')) {
                            switch ($carrierConfig->get('type')) {
                                case 'pickup':
                                    $carry[$carrierName." (".__('PVZ').")"] = [];
                                    break;

                                case 'carrier':
                                    $carry[$carrierName." (".__('Courier').")"] = [];
                                    break;
                            }
                        }

                        return $carry;
                    },
                    []
                )
            )->sortKeys()->toArray(),
            [__('Unknown') => []]
        );

        $carriersAndBrandsGroupsTemplate = array_fill_keys(
            array_keys($carriersGroupsTemplate),
            ['subgroups' => $brandsGroupsTemplate]
        );

        $reportsData = [
            'reports' => [
                'byChannels' => __('by Channels'),
                'byBrands' => __('By Brands'),
                'byCarriers' => __('By Carriers'),
                'byCarriersAndBrands' => __('By Carriers And Brands'),
            ],
            'data' => [
                'byChannels' => [
                    'titles' => [
                        'date' => __('Date'),
                        'allOrdersSum' => __('Sent Orders Sum'),
                        'purchasedOrdersSum' => __('Purchased Orders Sum'),
                        'expectedOrdersSum' => __('Expected Orders Sum'),
                        'allOrdersQuantity' => __('Sent Orders Quantity'),
                        'purchasedOrdersQuantity' => __('Purchased Orders Quantity'),
                        'purchasedOrdersPercentBySum' => __('Purchased Orders Percent by Sum'),
                        'purchasedOrdersPercentByQuantity' => __('Purchased Orders Percent by Quantity'),
                    ],
                    'groups' => $channelsGroupsTemplate,
                    'total' => [],
                ],
                'byBrands' => [
                    'titles' => [
                        'date' => __('Date'),
                        'allOrdersSum' => __('Sent Orders Sum'),
                        'purchasedOrdersSum' => __('Purchased Orders Sum'),
                        'expectedOrdersSum' => __('Expected Orders Sum'),
                        'allOrdersQuantity' => __('Sent Orders Quantity'),
                        'purchasedOrdersQuantity' => __('Purchased Orders Quantity'),
                        'purchasedOrdersPercentBySum' => __('Purchased Orders Percent by Sum'),
                        'purchasedOrdersPercentByQuantity' => __('Purchased Orders Percent by Quantity'),
                    ],
                    'groups' => $brandsGroupsTemplate,
                    'total' => [],
                ],
                'byCarriers' => [
                    'titles' => [
                        'date' => __('Date'),
                        'allOrdersSum' => __('Sent Orders Sum'),
                        'purchasedOrdersSum' => __('Purchased Orders Sum'),
                        'expectedOrdersSum' => __('Expected Orders Sum'),
                        'allOrdersQuantity' => __('Sent Orders Quantity'),
                        'purchasedOrdersQuantity' => __('Purchased Orders Quantity'),
                        'purchasedOrdersPercentBySum' => __('Purchased Orders Percent by Sum'),
                        'purchasedOrdersPercentByQuantity' => __('Purchased Orders Percent by Quantity'),
                    ],
                    'groups' => $carriersGroupsTemplate,
                    'total' => [],
                ],
                'byCarriersAndBrands' => [
                    'titles' => [
                        'date' => __('Date'),
                        'allOrdersSum' => __('Sent Orders Sum'),
                        'purchasedOrdersSum' => __('Purchased Orders Sum'),
                        'expectedOrdersSum' => __('Expected Orders Sum'),
                        'allOrdersQuantity' => __('Sent Orders Quantity'),
                        'purchasedOrdersQuantity' => __('Purchased Orders Quantity'),
                        'purchasedOrdersPercentBySum' => __('Purchased Orders Percent by Sum'),
                        'purchasedOrdersPercentByQuantity' => __('Purchased Orders Percent by Quantity'),
                    ],
                    'groups' => $carriersAndBrandsGroupsTemplate,
                    'total' => [],
                ],
            ],
        ];

        $dateTemplate = [];

        while ($dateFrom->getTimestamp() < $dateTo->getTimestamp()) {
            $dateTemplate[$dateFrom->format('d-m-Y')] = [];
            $dateFrom = $dateFrom->addDay();
        }

        foreach ($reportsData['data'] as $dataReportId => &$dataReport) {
            $rowTemplate = array_fill_keys(array_keys($dataReport['titles']), 0);

            $groupTemplate = [
                'rows' => [],
                'total' => $rowTemplate,
            ];
            foreach ($dateTemplate as $rowName => $row) {
                $groupTemplate['rows'][$rowName] = $rowTemplate;
                $groupTemplate['rows'][$rowName]['date'] = $rowName;
            }

            foreach ($dataReport['groups'] as $groupName => &$group) {

                if (isset($group['subgroups']) && is_array($group['subgroups'])) {
                    foreach ($group['subgroups'] as $subGroupName => &$subGroup) {
                        $subGroup = $groupTemplate;
                        $subGroup['total']['date'] = $subGroupName.": ".__('Total');
                    }
                    $group['total'] = $rowTemplate;
                } else {
                    $group = $groupTemplate;
                }
                $group['total']['date'] = $groupName.": ".__('Total');
            }

            $dataReport['total'] = $rowTemplate;
            $dataReport['total'] ['date'] = __('Total');
        }

        //endregion

        //region заполнение первичными данными
        $orders->each(
            function (Order $order) use (&$reportsData) {

                $isCompleted = false;

                $orderSentState = $order->states()->where('is_sent', 1)->first();

                if ($orderSentState instanceof OrderState) {
                    if (Carbon::createFromTimeString($orderSentState->getOriginal('pivot_created_at'))->addDays(
                            30
                        )->getTimestamp() < now()->getTimestamp()) {
                        $isCompleted = true;
                    } else {
                        $isCompleted = $order->orderDetails->every(
                            function (OrderDetail $orderDetail) {

                                $lastNeedStateStore = $orderDetail
                                    ->states()
                                    ->whereIn('store_operation', array_keys(Operation::OPERATION_TYPES))
                                    ->first();

                                $lastNeedStateCustomer = $orderDetail
                                    ->states()
                                    ->whereIn(
                                        'product_operation_by_order',
                                        array_keys(VirtualOperation::OPERATION_TYPES)
                                    )
                                    ->first();

                                return (($lastNeedStateStore instanceof OrderDetailState) && $lastNeedStateStore->store_operation == 'D')
                                    || (($lastNeedStateCustomer instanceof OrderDetailState) && $lastNeedStateCustomer->product_operation_by_order == 'D');
                            }
                        );
                    }
                }


                if (!$isCompleted) {
                    return true;
                }

                $orderDetails = $order->orderDetails->reduce(
                    function (array $carry, OrderDetail $orderDetail) {

                        if ($orderDetail->product->isNeedGuarantee()) {
                            $carry['Guarantee'][] = $orderDetail;
                        } else {
                            $carry['NoGuarantee'][] = $orderDetail;
                        }

                        return $carry;
                    },
                    [
                        'Guarantee' => [],
                        'NoGuarantee' => [],
                    ]
                );

                $orderDetails['Guarantee'] = $orderDetails['Guarantee'] ?? [];
                $orderDetails['NoGuarantee'] = $orderDetails['NoGuarantee'] ?? [];

                $orderDetails = !empty($orderDetails['Guarantee']) ? $orderDetails['Guarantee'] : $orderDetails['NoGuarantee'];

                $brand = __('Unknown');
                $expectedSum = 0;

                if (!empty($orderDetails)) {
                    /**
                     * @var OrderDetail $orderDetail
                     */
                    $orderDetail = collect($orderDetails)->sortBy('price')->first();
                    $brand = $orderDetail->product->manufacturer->name;
                    $expectedSum = round($orderDetail->price * $orderDetail->currency->currency_rate, 2);
                }

                $orderSum = $order->orderDetails->sum('price');
                $purchasedSum = $order->orderDetails->filter(
                    function (OrderDetail $orderDetail) {
                        $orderDetailOperationState = $orderDetail->states()->whereIn(
                            'product_operation_by_order',
                            ['C', 'D']
                        )->first();

                        return $orderDetailOperationState instanceof OrderDetailState && $orderDetailOperationState->product_operation_by_order == 'D';

                    }
                )->sum('price');

                $reportsData['data']['byChannels']['groups'][$order->channel->name]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['allOrdersQuantity']++;
                $reportsData['data']['byChannels']['groups'][$order->channel->name]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['allOrdersSum'] += $orderSum;
                $reportsData['data']['byChannels']['groups'][$order->channel->name]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['purchasedOrdersSum'] += $purchasedSum;
                $reportsData['data']['byChannels']['groups'][$order->channel->name]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['purchasedOrdersQuantity'] += ($purchasedSum > 0 ? 1 : 0);
                $reportsData['data']['byChannels']['groups'][$order->channel->name]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['expectedOrdersSum'] += $expectedSum;
                $reportsData['data']['byBrands']['groups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['allOrdersQuantity']++;
                $reportsData['data']['byBrands']['groups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['allOrdersSum'] += $orderSum;
                $reportsData['data']['byBrands']['groups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['purchasedOrdersSum'] += $purchasedSum;
                $reportsData['data']['byBrands']['groups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['purchasedOrdersQuantity'] += ($purchasedSum > 0 ? 1 : 0);
                $reportsData['data']['byBrands']['groups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['expectedOrdersSum'] += $expectedSum;


                /**
                 * @var Carrier $carrier
                 */
                $carrier = $order->carrier;

                $carrierConfig = $carrier instanceof Carrier ? $carrier->getConfigVars() : collect();

                $carrierName = __('Unknown');

                if ($carrier instanceof Carrier) {
                    $carrierName = [];
                    preg_match('/\[([^\[\]]*)\]/', $carrier->name, $carrierName);
                    $carrierName = isset($carrierName[1]) ? $carrierName[1] : $carrier->name;
                }


                $reportsData['data']['byCarriers']['groups'][$carrierName]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['allOrdersQuantity']++;
                $reportsData['data']['byCarriers']['groups'][$carrierName]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['allOrdersSum'] += $orderSum;
                $reportsData['data']['byCarriers']['groups'][$carrierName]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['purchasedOrdersSum'] += $purchasedSum;
                $reportsData['data']['byCarriers']['groups'][$carrierName]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['purchasedOrdersQuantity'] += ($purchasedSum > 0 ? 1 : 0);
                $reportsData['data']['byCarriers']['groups'][$carrierName]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['expectedOrdersSum'] += $expectedSum;

                $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName]['subgroups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['allOrdersQuantity']++;
                $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName]['subgroups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['allOrdersSum'] += $orderSum;
                $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName]['subgroups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['purchasedOrdersSum'] += $purchasedSum;
                $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName]['subgroups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['purchasedOrdersQuantity'] += ($purchasedSum > 0 ? 1 : 0);
                $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName]['subgroups'][$brand]['rows'][$order->created_at->format(
                    'd-m-Y'
                )]['expectedOrdersSum'] += $expectedSum;

                if ($carrierConfig->has('type')) {
                    switch ($carrierConfig->get('type')) {
                        case 'pickup':
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['notForTotal'] = true;
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['rows'][$order->created_at->format('d-m-Y')]['allOrdersQuantity']++;
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['allOrdersSum'] += $orderSum;
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['purchasedOrdersSum'] += $purchasedSum;
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['purchasedOrdersQuantity'] += ($purchasedSum > 0 ? 1 : 0);
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['expectedOrdersSum'] += $expectedSum;

                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['notForTotal'] = true;
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['allOrdersQuantity']++;
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['allOrdersSum'] += $orderSum;
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['purchasedOrdersSum'] += $purchasedSum;
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['purchasedOrdersQuantity'] += ($purchasedSum > 0 ? 1 : 0);
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'PVZ'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['expectedOrdersSum'] += $expectedSum;
                            break;

                        case 'carrier':
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['notForTotal'] = true;
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['rows'][$order->created_at->format('d-m-Y')]['allOrdersQuantity']++;
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['allOrdersSum'] += $orderSum;
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['purchasedOrdersSum'] += $purchasedSum;
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['purchasedOrdersQuantity'] += ($purchasedSum > 0 ? 1 : 0);
                            $reportsData['data']['byCarriers']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['expectedOrdersSum'] += $expectedSum;

                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['notForTotal'] = true;
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['allOrdersQuantity']++;
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['allOrdersSum'] += $orderSum;
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['purchasedOrdersSum'] += $purchasedSum;
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['purchasedOrdersQuantity'] += ($purchasedSum > 0 ? 1 : 0);
                            $reportsData['data']['byCarriersAndBrands']['groups'][$carrierName." (".__(
                                'Courier'
                            ).")"]['subgroups'][$brand]['rows'][$order->created_at->format(
                                'd-m-Y'
                            )]['expectedOrdersSum'] += $expectedSum;
                            break;
                    }
                }
            }
        );
        //endregion

        foreach ($reportsData['data'] as &$report) {
            $totalsGroups = collect();
            foreach ($report['groups'] as $key => &$group) {

                if (isset($group['subgroups']) && is_array($group['subgroups'])) {
                    $rows = collect();
                    foreach ($group['subgroups'] as $subGroupKey => &$subGroup) {
                        foreach ($subGroup['rows'] as &$row) {
                            $row['purchasedOrdersPercentBySum'] = $row['expectedOrdersSum'] > 0 ? round(
                                $row['purchasedOrdersSum'] / $row['expectedOrdersSum'] * 100,
                                2
                            ) : 0;
                            $row['purchasedOrdersPercentByQuantity'] = $row['allOrdersQuantity'] > 0 ? round(
                                $row['purchasedOrdersQuantity'] / $row['allOrdersQuantity'] * 100,
                                2
                            ) : 0;
                        }

                        $subGroupRows = collect($subGroup['rows']);

                        $subGroup['total'] = array_merge(
                            $subGroup['total'],
                            [
                                'allOrdersSum' => $subGroupRows->sum('allOrdersSum'),
                                'purchasedOrdersSum' => $subGroupRows->sum('purchasedOrdersSum'),
                                'expectedOrdersSum' => $subGroupRows->sum('expectedOrdersSum'),
                                'allOrdersQuantity' => $subGroupRows->sum('allOrdersQuantity'),
                                'purchasedOrdersQuantity' => $subGroupRows->sum('purchasedOrdersQuantity'),
                            ]
                        );

                        $subGroup['total']['purchasedOrdersPercentBySum'] = $subGroup['total']['expectedOrdersSum'] > 0 ? round(
                            $subGroup['total']['purchasedOrdersSum'] / $subGroup['total']['expectedOrdersSum'] * 100,
                            2
                        ) : 0;
                        $subGroup['total']['purchasedOrdersPercentByQuantity'] = $subGroup['total']['allOrdersQuantity'] > 0 ? round(
                            $subGroup['total']['purchasedOrdersQuantity'] / $subGroup['total']['allOrdersQuantity'] * 100,
                            2
                        ) : 0;

                        if (collect($subGroup['total'])->filter(
                            function ($item) {
                                return !is_string($item) && $item > 0;
                            }
                        )->isEmpty()) {
                            unset($report['groups'][$key]['subgroups'][$subGroupKey]);
                            continue;
                        }

                        if (!($subGroup['notForTotal'] ?? false)) {
                            $rows->push($subGroup['total']);
                        }
                    }
                } else {
                    foreach ($group['rows'] as &$row) {
                        $row['purchasedOrdersPercentBySum'] = $row['expectedOrdersSum'] > 0 ? round(
                            $row['purchasedOrdersSum'] / $row['expectedOrdersSum'] * 100,
                            2
                        ) : 0;
                        $row['purchasedOrdersPercentByQuantity'] = $row['allOrdersQuantity'] > 0 ? round(
                            $row['purchasedOrdersQuantity'] / $row['allOrdersQuantity'] * 100,
                            2
                        ) : 0;
                    }

                    $rows = collect($group['rows']);
                }


                $group['total'] = array_merge(
                    $group['total'],
                    [
                        'allOrdersSum' => $rows->sum('allOrdersSum'),
                        'purchasedOrdersSum' => $rows->sum('purchasedOrdersSum'),
                        'expectedOrdersSum' => $rows->sum('expectedOrdersSum'),
                        'allOrdersQuantity' => $rows->sum('allOrdersQuantity'),
                        'purchasedOrdersQuantity' => $rows->sum('purchasedOrdersQuantity'),
                    ]
                );

                $group['total']['purchasedOrdersPercentBySum'] = $group['total']['expectedOrdersSum'] > 0 ? round(
                    $group['total']['purchasedOrdersSum'] / $group['total']['expectedOrdersSum'] * 100,
                    2
                ) : 0;
                $group['total']['purchasedOrdersPercentByQuantity'] = $group['total']['allOrdersQuantity'] > 0 ? round(
                    $group['total']['purchasedOrdersQuantity'] / $group['total']['allOrdersQuantity'] * 100,
                    2
                ) : 0;

                if (collect($group['total'])->filter(
                    function ($item) {
                        return !is_string($item) && $item > 0;
                    }
                )->isEmpty()) {
                    unset($report['groups'][$key]);
                    continue;
                }

                if (!($group['notForTotal'] ?? false)) {
                    $totalsGroups->push($group['total']);
                }
            }

            $report['total'] = array_merge(
                $report['total'],
                [
                    'allOrdersSum' => $totalsGroups->sum('allOrdersSum'),
                    'purchasedOrdersSum' => $totalsGroups->sum('purchasedOrdersSum'),
                    'expectedOrdersSum' => $totalsGroups->sum('expectedOrdersSum'),
                    'allOrdersQuantity' => $totalsGroups->sum('allOrdersQuantity'),
                    'purchasedOrdersQuantity' => $totalsGroups->sum('purchasedOrdersQuantity'),
                ]
            );

            $report['total']['purchasedOrdersPercentBySum'] = $report['total']['expectedOrdersSum'] > 0 ? round(
                $report['total']['purchasedOrdersSum'] / $report['total']['expectedOrdersSum'] * 100,
                2
            ) : 0;
            $report['total']['purchasedOrdersPercentByQuantity'] = $report['total']['allOrdersQuantity'] > 0 ? round(
                $report['total']['purchasedOrdersQuantity'] / $report['total']['allOrdersQuantity'] * 100,
                2
            ) : 0;
        }

        renderView:
        $from = !is_null($from) ? $from->format('d-m-Y') : null;
        $to = !is_null($to) ? $to->format('d-m-Y') : null;
        $sub_report_selected = $request->sub_report_selected;
        $submit = $request->submit;
        $reportsData = $reportsData ?? [];

        return view(
            'analytics.reports.delivery.main',
            compact('from', 'to', 'sub_report_selected', 'submit', 'reportsData')
        );
    }

    /**
     * Get the prices of the products in CSV
     */
    public function productsGetCSV()
    {
        $data = Product::all()->map(
            function (Product $product) {
                return collect(
                    [
                        'name' => $product->name,
                        'reference' => $product->reference,
                        'price' => $product->getPrice() ?: '0',
                    ]
                );
            }
        );

        if ($data->count() == 0) {
            $data->push(
                collect(
                    [
                        'name' => '',
                        'reference' => '',
                        'price' => '',
                    ]
                )
            );
        }

        $data = $data->toArray();

        Excel::create(
            'products_'.Carbon::now()->format('d_m_Y_H_i'),
            function (LaravelExcelWriter $excel) use ($data) {
                $excel->sheet(
                    'Sheet',
                    function (LaravelExcelWorksheet $sheet) use ($data) {
                        $sheet->fromArray($data);
                    }
                );
            }
        )->download('csv');
    }

    /**
     * @param \App\Http\Requests\CsvImportRequest $request
     * @return \Illuminate\Http\Response
     */
    public function productsImportCSV(CsvImportRequest $request)
    {
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        if ($file->getClientOriginalExtension() != 'csv') {
            throw ValidationException::withMessages([__('Need a CSV file.')]);
        }
        $data = Excel::load(
            $path,
            function ($reader) {
            }
        )->get()->toArray();

        if (count($data) < 1) {
            throw ValidationException::withMessages([__('CSV file is Empty.')]);
        }

        collect(['reference', 'price'])->each(
            function ($item) use ($data) {
                if (!collect($data[0])->has($item)) {
                    throw ValidationException::withMessages([__('CSV file no need column:').' '.$item]);
                }
            }
        );

        $errorMessages = collect();
        foreach ($data as $item) {
            $product = Product::where('reference', $item['reference'])->first();
            if ($product) {
                $product->update(['wholesale_price' => (float)$item['price']]);
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

        return back()->with('success', __('Import successful'))->withErrors($errorMessages);
    }

    /**
     * @param string $account
     * @param string $password
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     * @throws \Exception
     */
    protected function getCdekDelivery($account, $password, Carbon $dateFrom, Carbon $dateTo)
    {
        $result = [];
        if (!empty($account) && !empty($password) && $dateFrom->timestamp <= $dateTo->timestamp) {
            $cdekClient = new CdekClient($account, $password);
            $cdekStart = (new \DateTimeImmutable($dateFrom->toDateTimeString()))->sub((new \DateInterval('P31D')));
            $cdekTo = new \DateTimeImmutable($dateTo->toDateTimeString());
            $cdekPeriod = new ChangePeriod($cdekStart, $cdekTo);
            $cdekRequest = (new InfoReportRequest())->setChangePeriod($cdekPeriod);
            $cdekInfoResult = collect($cdekClient->sendInfoReportRequest($cdekRequest)->getOrders())->keyBy(
                function (cdekOrder $order) {
                    return $order->getDispatchNumber();
                }
            );

            $cdekRequest = (new StatusReportRequest())->setShowHistory();
            foreach ($cdekInfoResult as $cdekOrder) {
                $cdekRequest->addOrder($cdekOrder);
            }
            $cdekResult = $cdekClient->sendStatusReportRequest($cdekRequest);
            $cdekStatusResult = collect($cdekResult->getOrders())->filter(
                function (cdekOrder $order) use (
                    $cdekInfoResult
                ) {
                    return $cdekInfoResult->has($order->getDispatchNumber()) && strlen(
                            $order->getMessage()
                        ) == 0 && ($order->getStatus()->getCode() == 4 || $order->getStatus()->getCode() == 5);
                }
            );

            /**
             * @var cdekOrder $cdekOrder
             */
            foreach ($cdekStatusResult as $cdekOrder) {
                $result[$cdekOrder->getDispatchNumber()]['StatusCode'] = $cdekOrder->getStatus()->getCode();
                $result[$cdekOrder->getDispatchNumber()]['StatusDescription'] = $cdekOrder->getStatus()->getDescription(
                );
                $result[$cdekOrder->getDispatchNumber()]['ReasonCode'] = $cdekOrder->getReason()->Code;
                $result[$cdekOrder->getDispatchNumber()]['ReasonDescription'] = $cdekOrder->getReason()->Description;
                $result[$cdekOrder->getDispatchNumber()]['OrderNumber'] = $cdekOrder->getNumber();
                if ($cdekOrder->DeliveryDate) {
                    $result[$cdekOrder->getDispatchNumber()]['Date'] = $cdekOrder->DeliveryDate->format('Y-m-d');
                } else {
                    $result[$cdekOrder->getDispatchNumber()]['Date'] = collect(
                        $cdekOrder->getStatus()->getStates()
                    )->reverse()->reduce(
                        function (
                            $data,
                            cdekState $state
                        ) {
                            /**
                             * @var \Appwilio\CdekSDK\Common\Status $currentState
                             */
                            $currentState = $data['State'];
                            $data['Date'] = ($state->Code == $currentState->getCode() ? $state->Date->format(
                                'Y-m-d'
                            ) : $data['Date']);

                            return $data;
                        },
                        [
                            'Date' => $cdekOrder->getStatus()->getDate()->format('Y-m-d'),
                            'State' => $cdekOrder->getStatus(),
                        ]
                    )['Date'];
                }

                $result[$cdekOrder->getDispatchNumber()]['PVZ'] = empty(
                $cdekInfoResult->get(
                    $cdekOrder->getDispatchNumber()
                )->getPvzCode()
                ) ? 0 : 1;


                $result[$cdekOrder->getDispatchNumber()]['CashOnDeliv'] = $cdekInfoResult->get(
                    $cdekOrder->getDispatchNumber()
                )->getCashOnDeliv();
                $result[$cdekOrder->getDispatchNumber()]['CashOnDelivFact'] = $cdekInfoResult->get(
                    $cdekOrder->getDispatchNumber()
                )->getCashOnDelivFact();
                $result[$cdekOrder->getDispatchNumber()]['DeliverySum'] = $cdekInfoResult->get(
                    $cdekOrder->getDispatchNumber()
                )->getDeliverySum();
                $result[$cdekOrder->getDispatchNumber()]['AddedServices'] = $cdekInfoResult->get(
                    $cdekOrder->getDispatchNumber()
                )->getAdditionalServices();
                $result[$cdekOrder->getDispatchNumber()]['AddedServiceSum'] = collect(
                    $cdekInfoResult->get($cdekOrder->getDispatchNumber())->getAdditionalServices()
                )->reduce(
                    function (
                        $sum,
                        AdditionalService $additionalService
                    ) {
                        return $sum + $additionalService->getSum();
                    },
                    0
                );
                $result[$cdekOrder->getDispatchNumber()]['PackageDeliverySum'] = $cdekInfoResult->get(
                        $cdekOrder->getDispatchNumber()
                    )->getDeliverySum() + collect(
                        $cdekInfoResult->get($cdekOrder->getDispatchNumber())->getAdditionalServices()
                    )->reduce(
                        function (
                            $sum,
                            AdditionalService $additionalService
                        ) {
                            return $sum + (in_array(
                                    $additionalService->getServiceCode(),
                                    [
                                        42,
                                    ]
                                ) ? 0 : $additionalService->getSum());
                        },
                        0
                    );
                $result[$cdekOrder->getDispatchNumber()]['PackageServiceSum'] = collect(
                    $cdekInfoResult->get($cdekOrder->getDispatchNumber())->getAdditionalServices()
                )->reduce(
                    function (
                        $sum,
                        AdditionalService $additionalService
                    ) {
                        return $sum + (in_array(
                                $additionalService->getServiceCode(),
                                [
                                    42,
                                ]
                            ) ? $additionalService->getSum() : 0);
                    },
                    0
                );
            }
        }

        return $result;
    }

    /**
     * Получение текущего курса доллара
     *
     * @return float|int
     */
    protected static function getUsdRate()
    {
        $usdRate = 1;

        if (\Cache::has('usd_rate')) {
            $usdRate = \Cache::get('usd_rate');
        } else {
            $response = Unirest\Request::get('https://www.cbr-xml-daily.ru/daily_json.js');

            if ($response->code == '200' && $response->body instanceof \stdClass) {
                $usdRate = (float)$response->body->Valute->USD->Value;
            }
        }

        return $usdRate;
    }

    /**
     * Запрос к API Яндекс.Метрика
     *
     * @param string $proxyUrl URL адрес GO-прокси
     * @param array $parameters Параметры запроса к API
     * @param array $headers Заголовки запроса
     * @param string $method наименование метода запроса к API
     * @param string $additionalFilters дополнительные фильтры запроса
     *
     * @return Unirest\Response
     */
    public static function requestYandexMetrika(
        $proxyUrl = null,
        array $parameters = [],
        array $headers = [],
        $method = 'stat',
        $additionalFilters = ''
    ) {
        $response = new Unirest\Response('500', '', '');

        try {
            $url = 'https://api-metrika.yandex.ru/';

            switch ($method) {
                case 'stat':
                    $url .= 'stat/v1/data';
                    break;
                case 'management':
                    $url .= 'management/v1/clients';
            }

            if (!empty($additionalFilters)) {
                $parameters['filters'] = (isset($parameters['filters']) ? $parameters['filters'].',' : '').$additionalFilters;
            }

            $url .= count($parameters) ?
                '?'.collect($parameters)
                    ->filter(
                        function ($value) {
                            return !is_null($value);
                        }
                    )
                    ->map(
                        function ($value, $name) {
                            return "{$name}={$value}";
                        }
                    )
                    ->implode('&')
                : $url;

            if (!is_null($proxyUrl)) {
                $headers['Go'] = $url;
                $url = $proxyUrl;
            }

            $response = Unirest\Request::get($url, $headers);

        } catch (Unirest\Exception $exception) {
            \Session::flash('warning', (\Session::get('warning') ?: '')."- Timeout for {$url};");
        }

        return $response;
    }


    /**
     * Парсинг ответа API Яндекс.Метрики
     *
     * @param Unirest\Response $response
     * @param string $method наименование метода запроса к API
     * @return bool|Collection
     */
    public static function parseResponseYandexMetrikaOrFail(Unirest\Response $response, $method = 'stat')
    {
        if ($response->code != '200' || !($response->body instanceof \stdClass)) {
            return false;
        }

        switch ($method) {
            case 'stat':
                if (!property_exists($response->body, 'query')
                    || !($response->body->query instanceof \stdClass)
                    || !property_exists($response->body->query, 'dimensions')
                    || !is_array($response->body->query->dimensions)
                    || !property_exists($response->body->query, 'metrics')
                    || !is_array($response->body->query->metrics)
                    || !property_exists($response->body, 'data')
                    || !is_array($response->body->data)
                ) {
                    return false;
                }

                $query = $response->body->query;

                return collect($response->body->data)->map(
                    function (\stdClass $item) use ($query) {

                        foreach ($item->dimensions as $index => $value) {
                            $item->dimensions[$query->dimensions[$index]] = $value;
                            unset($item->dimensions[$index]);
                        }

                        foreach ($item->metrics as $index => $value) {
                            $item->metrics[$query->metrics[$index]] = $value;
                            unset($item->metrics[$index]);
                        }

                        return $item;
                    }
                );

                break;

            case 'management':

                if (!property_exists($response->body, 'clients')
                    || !is_array($response->body->clients)
                    || !count($response->body->clients)
                ) {
                    return false;
                }

                return collect($response->body->clients);

                break;
        }

        return false;
    }

    /**
     * Аналитика пользователей
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function users(Request $request)
    {
        //Создание первоночальных селекторов выбора даты, группы и пользователей
        $userSelector = [];
        $usersByGroup = [];
        $allUsers = User::pluck('name', 'id');
        $roles = Role::orderBy('name')->pluck('name', 'id');
        $userRoles = DB::table('model_has_roles')->pluck('role_id', 'model_id');
        foreach ($userRoles as $model_id => $role_id) {
            $userSelector[$roles[$role_id]][] = $allUsers[$model_id];
            $usersByGroup[$roles[$role_id]][] = User::where('id', $model_id)->first();
        }
        foreach ($userSelector as &$group) {
            array_unshift($group, __('All users'));
        }
        foreach ($usersByGroup as $key => &$group) {
            array_unshift($group, __('All users'));
        }
        $userSelector = json_encode($userSelector, JSON_UNESCAPED_UNICODE);
        $data = [];
        //true если пользователь выполнил какие либо действия (показать или сохранить отчёт)
        if ($request->report) {
            //true если пользователь требует показать отчёт
            if ($request->submit) {

                $dataFrom = is_null($request->from) || !isset($request->from) ? null : Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $request->from
                )->toDateTimeString();
                $dataTo = is_null($request->to) || !isset($request->to) ? null : Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $request->to
                )->toDateTimeString();
                $roleObject = Role::where('name', $roles->values()[$request->role])->first();
                //выборка данных для роли "менеджер
                if ($roleObject->is_manager) {
                    //выбранные поля в селекорах
                    $successfulOrderStates = isset($request->successful) && array_key_exists(
                        'OrderState',
                        $request->successful
                    ) && isset($request->successful['OrderState']) ? $request->successful['OrderState'] : [];
                    $successfulTaskStates = isset($request->successful) && array_key_exists(
                        'TaskState',
                        $request->successful
                    ) && isset($request->successful['TaskState']) ? $request->successful['TaskState'] : [];
                    $successfulTransferedTasks = isset($request->successful) && array_key_exists(
                        'TransferedTasks',
                        $request->successful
                    ) && isset($request->successful['TransferedTasks']) ? $request->successful['TransferedTasks'] : [];
                    $userId = $request->user != 0 ? $usersByGroup[$roleObject->name][$request->user]->id : 0;
                    //данные по всем статусам заказов
                    if (in_array(0, $successfulOrderStates)) {
                        $allOrders = Order::query()
                            ->select(
                                [
                                    'orders.*',
                                    'order_order_state.order_state_id as state_id',
                                ]
                            )
                            ->Join(
                                'order_order_state',
                                'orders.id',
                                'order_order_state.order_id'
                            );
                        //если требуется данные по всем пользователям то фильтрация пользователей не требуется
                        if ($userId != 0) {
                            $allOrders->where('order_order_state.user_id', $userId);
                        }
                        //фильтрация по выбранному диапазону дат
                        $allOrders = $allOrders->whereBetween(
                            'order_order_state.created_at',
                            [
                                $dataFrom,
                                $dataTo,
                            ]
                        )
                            ->get();
                        //общее время звонков считается только для всех пользователей
                        $allCallLength = 0;
                        if ($userId == 0) {
                            $notCountedCalls = Call::whereBetween(
                                'created_at',
                                [
                                    $dataFrom,
                                    $dataTo,
                                ]
                            )->where('length', null)
                                ->where('recordUrl', '<>', null)
                                ->where('recordUrl', '<>', '')
                                ->get();
                            $notCountedCalls->each(
                                function ($item, $key) {
                                    $item->callLength();
                                }
                            );
                            $allCallLength = Call::whereBetween(
                                'created_at',
                                [
                                    $dataFrom,
                                    $dataTo,
                                ]
                            )->sum('length');
                        }
                        //добавление в вывод информации по колличеству задач
                        $information = [
                            __('Total') => count($allOrders),
                        ];
                        //добавление в вывод информации по продолжительности звонков
                        if ($allCallLength != 0) {
                            $information[__("Calls length")] = ((int)($allCallLength / 3600)).':'.date(
                                    "i:s",
                                    mktime(0, 0, $allCallLength)
                                );
                        }
                        //генерация вывода таблицы заказов для всех статусов
                        $tableRows = [];
                        if (count($allOrders) > 0) {
                            $dataGenerator = [
                                'name' => __('All orders'),
                                'information' => $information,
                                'tablecolumns' => [
                                    __("Order"),
                                    __("Channel"),
                                    __("Delivery"),
                                    __("Customer"),
                                    __("Phone"),
                                ],
                            ];
                            foreach ($allOrders as $order) {
                                $tableRows[] =
                                    [
                                        'rowcolumns' => [
                                            [
                                                'link' => route('orders.edit', ['id' => $order->id]),
                                                'text' => $order->getDisplayNumber(),
                                            ],
                                            [
                                                'text' => $order->channel->name,
                                            ],
                                            [
                                                'text' => $order->channel->carrier,
                                            ],
                                            [
                                                'text' => $order->customer->first_name.$order->customer->last_name,
                                            ],
                                            [
                                                'text' => $order->customer->phone,
                                            ],
                                        ],

                                        'color' => $order->states[0]->color,
                                    ];
                            }
                            $dataGenerator['tablerows'] = $tableRows;
                            $data[] = $dataGenerator;
                        }
                    }
                    //данные по выбранным статусам заказов
                    $orders = Order::query()
                        ->select(
                            [
                                'orders.*',
                                'order_order_state.order_state_id as state_id',
                            ]
                        )
                        ->Join(
                            'order_order_state',
                            'orders.id',
                            'order_order_state.order_id'
                        )->distinct('id');
                    //если требуется данные по всем пользователям то фильтрация пользователей не требуется
                    if ($userId != 0) {
                        $orders->where('order_order_state.user_id', $userId);
                    }
                    //фильтрация по выбранному диапазону дат
                    $orders = $orders->whereIn('order_order_state.order_state_id', $successfulOrderStates)
                        ->whereBetween(
                            'order_order_state.created_at',
                            [
                                $dataFrom,
                                $dataTo,
                            ]
                        )
                        ->get()
                        //группировка вывода данных по статусу заказа
                        ->groupBy('state_id');
                        //генерация вывода таблицы заказов для выбранных статусов
                    foreach ($orders as $key => $group) {
                        $tableRows = [];
                        $dataGenerator = [
                            'name' => OrderState::find($key)['name'],
                            'information' => [__('Total') => count($group)],
                            'tablecolumns' => [
                                __("Order"),
                                __("Channel"),
                                __("Delivery"),
                                __("Customer"),
                                __("Phone"),
                            ],
                        ];
                        foreach ($group as $order) {
                            $tableRows[] =
                                [
                                    'rowcolumns' => [
                                        [
                                            'link' => route('orders.edit', ['id' => $order->id]),
                                            'text' => $order->getDisplayNumber(),
                                        ],
                                        [
                                            'text' => $order->channel->name,
                                        ],
                                        [
                                            'text' => $order->channel->carrier,
                                        ],
                                        [
                                            'text' => $order->customer->first_name.$order->customer->last_name,
                                        ],
                                        [
                                            'text' => $order->customer->phone,
                                        ],
                                    ],

                                    'color' => $order->states[0]->color,
                                ];
                        }
                        $dataGenerator['tablerows'] = $tableRows;
                        $data[] = $dataGenerator;
                    }
                    //данные по всем статусам задач
                    if (in_array(0, $successfulTaskStates)) {
                        $allTasks = Task::query()
                            ->select(
                                [
                                    'tasks.*',
                                    'task_task_state.task_state_id as state_id',
                                ]
                            )
                            ->Join(
                                'task_task_state',
                                'tasks.id',
                                'task_task_state.task_id'
                            );
                        //если требуется данные по всем пользователям то фильтрация пользователей не требуется
                        if ($userId != 0) {
                            $allTasks->where('task_task_state.user_id', $userId);
                        }
                        //фильтрация по выбранному диапазону дат
                        $allTasks = $allTasks->whereBetween(
                            'task_task_state.created_at',
                            [
                                $dataFrom,
                                $dataTo,
                            ]

                        )
                            ->get();
                        //генерация вывода таблицы задач для всех статусов
                        if (count($allTasks) > 0) {
                            $tableRows = [];
                            $dataGenerator = [
                                'name' => __('All tasks'),
                                'information' => [__('Total') => count($allTasks)],
                                'tablecolumns' => [
                                    __("Task"),
                                    __("Order"),
                                    __("Theme"),
                                    __("Description"),
                                    __("Deadline"),
                                ],
                            ];
                            foreach ($allTasks as $task) {
                                $tableRows[] = $this->getTaskRow($task);
                            }
                            $dataGenerator['tablerows'] = $tableRows;
                            $data[] = $dataGenerator;
                        }
                    }
                    //данные по выбранным статусам задач
                    $tasks = Task::query()
                        ->select(
                            [
                                'tasks.*',
                                'task_task_state.task_state_id as state_id',
                            ]
                        )
                        ->Join(
                            'task_task_state',
                            'tasks.id',
                            'task_task_state.task_id'
                        );
                    //если требуется данные по всем пользователям то фильтрация пользователей не требуется
                    if ($userId != 0) {
                        $tasks->where('task_task_state.user_id', $userId);
                    }
                    //фильтрация по выбранному диапазону дат
                    $tasks = $tasks->whereIn('task_task_state.task_state_id', $successfulTaskStates)
                        ->whereBetween(
                            'task_task_state.created_at',
                            [
                                $dataFrom,
                                $dataTo,
                            ]

                        )
                        ->get()
                        //группировка вывода данных по статусу задач
                        ->groupBy('state_id');
                    //генерация вывода таблицы задач для выбранных статусов
                    foreach ($tasks as $key => $group) {
                        $tableRows = [];
                        $dataGenerator = [
                            'name' => TaskState::find($key)['name'],
                            'information' => [__('Total') => count($group)],
                            'tablecolumns' => [
                                __("Task"),
                                __("Order"),
                                __("Theme"),
                                __("Description"),
                                __("Deadline"),
                            ],
                        ];
                        foreach ($group as $task) {
                            $tableRows[] = $this->getTaskRow($task);
                        }
                        $dataGenerator['tablerows'] = $tableRows;
                        $data[] = $dataGenerator;
                    }
                    //проверка требуется ли вывод перенесённых задач
                    if (isset($successfulTransferedTasks[0]) && $successfulTransferedTasks[0]) {
                        $transferedTasks = $changedDates = ModelChange::getModelsChenges(Task::class, 'deadline_date')
                            ->whereBetween(
                                'created_at',
                                [
                                    $dataFrom,
                                    $dataTo,
                                ]

                            );
                        //если требуется данные по всем пользователям то фильтрация пользователей не требуется
                        if ($userId != 0) {
                            $transferedTasks = $transferedTasks->where('user_id', $userId);
                        }
                        //получение перенесённых задач и исключение из списка завершённых или тех для которых deadline_date только что создана
                        $transferedTasks = $transferedTasks->get();
                        $transferedTasksList = [];
                        $taskDate = \Illuminate\Support\Carbon::createFromFormat('Y-m-d\TH:i', $request->to)->setTime(23,59,59,0);
                        foreach ($transferedTasks as  $transferedTask) {
                            if (empty($transferedTask->old_values()['deadline_date']) || $transferedTask->old_values()['deadline_date'] == $transferedTask->new_values()['deadline_date']) continue;
                            $taskObject = Task::find($transferedTask->old_values()['id']);
                            if ($taskObject->stateOnDate($taskDate)->is_closed) continue;
                            $transferedTasksList[$taskObject->id] = $taskObject;
                        }
                        //генерация вывода таблицы перенесённых задач
                        $tableRows = [];
                        $dataGenerator = [
                            'name' => __('Transfered tasks'),
                            'information' => [__('Total') => count($transferedTasksList)],
                            'tablecolumns' => [
                                __("Task"),
                                __("Order"),
                                __("Theme"),
                                __("Description"),
                                __("Deadline"),
                            ],
                        ];
                        foreach ($transferedTasksList as $task) {
                            $tableRows[] = $this->getTaskRow($task);
                        }
                        $dataGenerator['tablerows'] = $tableRows;
                        $data[] = $dataGenerator;
                    }
                }
            }
            //true если пользователь требует сохранить настройки
            if ($request->save) {
                //сохранение настроек для текущего пользователя
                Configuration::updateOrCreate(
                    ['name' => 'usersAnalytics_'.\Auth::user()->getAuthIdentifier()],
                    [
                        'values' => json_encode(
                            [
                                'successful' => $request->successful,
                                'dateFrom' => is_null($request->from) ? null : Carbon::now()
                                    ->diffInMinutes(
                                        Carbon::createFromFormat('Y-m-d\TH:i', $request->from),
                                        false
                                    ),
                                'dateTo' => is_null($request->to) ? null : Carbon::now()
                                    ->diffInMinutes(
                                        Carbon::createFromFormat('Y-m-d\TH:i', $request->to),
                                        false
                                    ),
                                'role' => $request->role,
                                'user' => $request->user,
                            ]
                        ),
                    ]
                );
            }
            $dateFrom = $request->from;
            $dateTo = $request->to;
            $role = $request->role;
            $user = $request->user;
            //Поиск требуемой для отчёта роли
            $roleObject = Role::where('name', $roles->values()[$request->role])->first();
            //Создание селекторов выбору данных отчёта для роли менеджер
            if ($roleObject->is_manager) {
                $selectors = [
                    'OrderState' => [
                        'label' => __('Order States'),
                        'list' => OrderState::pluck('name', 'id'),
                        'successful' => isset($request->successful) && array_key_exists(
                            'OrderState',
                            $request->successful
                        ) && isset($request->successful['OrderState']) ? $request->successful['OrderState'] : [],
                    ],
                    'TaskState' => [
                        'label' => __('Task States'),
                        'list' => TaskState::pluck('name', 'id'),
                        'successful' => isset($request->successful) && array_key_exists(
                            'TaskState',
                            $request->successful
                        ) && isset($request->successful['TaskState']) ? $request->successful['TaskState'] : [],
                    ],
                    'TransferedTasks' => [
                        'label' => __('Transfered tasks'),
                        'list' => [
                            1 => __('Yes')
                        ],
                        'successful' => isset($request->successful) && array_key_exists(
                            'TransferedTasks',
                            $request->successful
                        ) && isset($request->successful['TransferedTasks']) ? $request->successful['TransferedTasks'] : [],
                    ],
                ];
                $selectors['OrderState']['list']->prepend(__('All orders'), 0);
                $selectors['TaskState']['list']->prepend(__('All tasks'), 0);
            } else {
                //пустой селектор если не предусмотрен обработчик для данной роли
                $selectors = [];
            }
        } else {
            //если пользователь заходит на страницу с аналитикой до совершения действий (сохранить/показать) то подгружаются его сохранённые селекторы если таковые существуют
            $configuration = Configuration::all()->where(
                'name',
                'usersAnalytics_'.\Auth::user()->getAuthIdentifier()
            )->first();
            $values = $configuration ? json_decode($configuration->values) : [];
            $successful = (array)(is_object($values) && isset($values->successful) ? $values->successful : null);
            $dateFrom = is_object($values) && isset($values->dateFrom) && !is_null($values->dateFrom) ? Carbon::now(
            )->addMinutes($values->dateFrom)->format('Y-m-d\TH:i') : null;
            $dateTo = is_object($values) && isset($values->dateTo) && !is_null($values->dateTo) ? Carbon::now(
            )->addMinutes($values->dateTo)->format('Y-m-d\TH:i') : null;

            $role = is_object($values) && isset($values->role) ? $values->role : null;
            $user = is_object($values) && isset($values->user) ? $values->user : null;
            //manager selectors loading
            $roleObject = !is_null($role) ? Role::where('name', $roles->values()[$role])->first() : null;
            if (!is_null($roleObject) && $roleObject->is_manager) {
                $selectors = [
                    'OrderState' => [
                        'label' => __('Order States'),
                        'list' => OrderState::pluck('name', 'id'),
                        'successful' => is_object(
                            $values
                        ) && isset($successful['OrderState']) ? $successful['OrderState'] : null,
                    ],
                    'TaskState' => [
                        'label' => __('Task States'),
                        'list' => TaskState::pluck('name', 'id'),
                        'successful' => is_object(
                            $values
                        ) && isset($successful['TaskState']) ? $successful['TaskState'] : null,
                    ],
                    'TransferedTasks' => [
                        'label' => __('Transfered tasks'),
                        'list' => [
                            1 => __('Yes')
                        ],
                        'successful' => is_object(
                            $values
                        ) && isset($successful['TransferedTasks']) ? $successful['TransferedTasks'] : null,
                    ]
                ];
                $selectors['OrderState']['list']->prepend(__('All orders'), 0);
                $selectors['TaskState']['list']->prepend(__('All tasks'), 0);
            } else {
                $selectors = [];
            }
        }

        return view(
            'analytics.users.managers',
            compact('selectors', 'data', 'role', 'user', 'dateFrom', 'dateTo', 'userSelector')
        );
    }

    /**
     * Получение строки с задачей для вывода для таблиц аналитики
     *
     * @param \App\Task $task
     * @return array
     */
    protected function getTaskRow(Task $task){
        $time = !is_null(
            $task->deadline_time
        ) || isset($task->deadline_time) ? $task->deadline_time : "23:59";

        return [
            'rowcolumns' => [
                [
                    'link' => route('tasks.edit', ['id' => $task->id]),
                    'text' => $task->id,
                ],
                [
                    'link' => route('orders.edit', ['id' => $task->order_id]),
                    'text' => $task->order->getDisplayNumber(),
                ],
                [
                    'text' => $task->name,
                ],
                [
                    'text' => $task->description,
                ],
                [
                    'text' => is_null(
                        $task->deadline_date
                    ) || !isset($task->deadline_date) ? '' : Carbon::createFromFormat(
                        'd-m-Y',
                        $task->deadline_date
                    )->setTimeFromTimeString($time)->toDateTimeString(),
                ],
            ],

            'color' => $task->states[0]->color,
        ];
    }

    /**
     * Получение количества уникальных ClientID из данных по кликам (таблица costs) Яндекс.Метрика Logs
     *
     * @param string|null $counter
     * @param string[] $wheres
     * @param bool $distinct
     * @return int
     */
    protected function getClientsQuantityFromMetrikaLogs(
        ?string $counter,
        array $wheres = [],
        bool $distinct = true
    ): int {

        $counter = $counter ?? '';

        /**
         * Массив настроек БД
         * @var array $dbConfig
         */
        $dbConfig = [
            'host' => config('clickhouse.host'),
            'port' => config('clickhouse.port'),
            'username' => config('clickhouse.username'),
            'password' => config('clickhouse.password'),
        ];

        /**
         * Клиент БД
         * @var Client $db
         */
        $db = new Client($dbConfig);
        $db->settings()->max_execution_time(200);

        /**
         * Имя БД
         * @var $dbName
         */
        $dbName = "db_{$counter}_ya_counter";

        if (!$db->isDatabaseExist($dbName) || !$db->isExists($dbName, 'costs')) {
            return 0;
        }

        $wheres = array_filter(
            $wheres,
            function ($item) {
                return !empty($item) && is_string($item);
            }
        );

        $sqlWhere = !empty($wheres) ? "WHERE ".implode(" AND ", $wheres) : '';

        $distinct = $distinct ? "DISTINCT" : "";

        return $db->select("SELECT {$distinct} ClientID FROM {$dbName}.costs {$sqlWhere}")->count();
    }
}
