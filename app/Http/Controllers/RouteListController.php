<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\Cashbox;
use App\City;
use App\CourierTask;
use App\CourierTaskState;
use App\Currency;
use App\Exceptions\DoingException;
use App\Http\Requests\RouteListManageRequest;
use App\Http\Requests\RouteListRequest;
use App\MapGeoCode;
use App\Operation;
use App\OperationState;
use App\Order;
use App\OrderDetail;
use App\OrderDetailState;
use App\OrderState;
use App\ProductExchange;
use App\ProductExchangeState;
use App\ProductReturn;
use App\ProductReturnState;
use App\Role;
use App\RouteList;
use App\RouteListState;
use App\RoutePoint;
use App\RoutePointState;
use App\Services\TripleFilterService\TripleFilterService;
use App\Store;
use App\User;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Ixudra\Curl\Facades\Curl;
use Spatie\Permission\Exceptions\UnauthorizedException;
use function GuzzleHttp\Psr7\str;

class RouteListController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:order-list', ['except' => ['indexOwn', 'viewOwn', 'actionOwn', 'actionOwnTask','courierLeft']]);
        $this->middleware('permission:fast-courier-left', ['only' => 'courierLeft']);
        $this->middleware('permission:order-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:order-edit', ['only' => ['edit', 'update', 'manageUpdate']]);
        $this->middleware('permission:route-lists-show-own', ['only' => ['indexOwn', 'viewOwn', 'actionOwn']]);
    }

    /**
     * Отображает список маршрутных листов
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $routeLists = RouteList::all()->filter(function (RouteList $routeList){
            return !$routeList->courier->is_not_working;
        });
        $paginatedItems = $this->paginate($routeLists,15);
        $paginatedItems->setPath($request->url());

        return view('route-lists.index', compact('paginatedItems'));
    }

    /**
     * Отображает список собственных маршрутных листов пользователя
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexOwn()
    {
        $routeList = RouteList::query()
            ->where('courier_id', \Auth::id())->first();

        if($routeList) {
            return redirect()->route('route-own-lists.view', $routeList->id);
        }

        return back();
    }

    /**
     * Просмотр собственного маршрутного листа
     *
     * @param RouteList $routeList
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewOwn(RouteList $routeList, Request $request)
    {
        set_time_limit(900);
        if ($routeList->courier_id !== \Auth::id()) {
            throw UnauthorizedException::forPermissions([]);
        }

        $orderDetails = collect();

        $routeList->orders()->each(
            function (Order $order) use (&$orderDetails) {
                $orderDetails = $orderDetails->merge($order->orderDetails);
            }
        );

        $routeList->productReturns()->each(
            function (ProductReturn $productReturn) use (&$orderDetails) {
                $orderDetails = $orderDetails->merge($productReturn->orderDetails);
            }
        );

        $routeList->productExchanges()->each(
            function (ProductExchange $productExchange) use (&$orderDetails) {
                $orderDetails = $orderDetails->merge($productExchange->orderDetails);
                $orderDetails = $orderDetails->merge($productExchange->exchangeOrderDetails);
            }
        );

        $orderDetailsByState = $orderDetails->groupBy(
            function (OrderDetail $orderDetail) {
                return $orderDetail->currentState()->name;
            }
        );

        $date = $request->date_list;

        if(isset($date)) {
            $date = Carbon::createFromFormat('d-m-Y', $date)->format('d-m-Y');
            $routeList->routePoints = $routeList->routePoints->filter(function (RoutePoint $routePoint) use ($date){
                if($routePoint->point_object_type == 'App\Order') {
                    return $routePoint->pointObject->date_estimated_delivery == $date;
                } else {
                    return $routePoint->pointObject->delivery_estimated_date == $date;
                }
            });
        }

        $pointStates = RoutePointState::all()->pluck('name', 'id');
        if(isset($request->pointState)) {
            $pointState = $request->pointState;
            $routeList->routePoints = $routeList->routePoints->filter(function (RoutePoint $routePoint) use ($pointState){
                return $routePoint->currentState()->id == $pointState;
            });
        }

        return view('route-lists-own.view', compact('routeList', 'orderDetailsByState', 'date', 'pointStates'));
    }

    /**
     * Отображение формы для создания нового маршрутного листа
     *
     * @return Response
     */
    public function create()
    {
        $courierRoles = Role::query()->where('is_courier', 1)->get();

        $couriers = User::all()
            ->filter(
                function (User $user) use ($courierRoles) {
                    return $user->hasAnyRole($courierRoles) && !$user->is_not_working;
                }
            )
            ->pluck('name', 'id');

        return view(
            'route-lists.create',
            compact(
                'couriers'
            )
        );
    }

    /**
     * Сохранение данных из формы создания нового маршрутного листа
     *
     * @param RouteListRequest $request
     * @return Response
     * @throws \Exception
     */
    public function store(RouteListRequest $request)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $routeList = RouteList::create($request->input());

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }


        DB::commit();

        return redirect()->route('route-lists.index')->with('success', __('Route List created successfully'));
    }


    /**
     * Метод оплаты за выбранные маршрутные точки
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function pay(Request $request)
    {
        $this->validate($request, [
            'points' => 'array|required',
            'courier' => 'integer|required',
        ]);

        if ($request->courier) {
            $points = $request->points;

            $collection = collect();
            foreach ($points as $point) {
                $collection->push(RoutePoint::find($point));
            }

            $courier = User::find($request->courier);
            $routeList = $courier->routeList;

            $cashboxes = Cashbox::all()->filter(
                function (Cashbox $cashbox) {
                    return $cashbox->users->where(
                        'id',
                        \Auth::id()
                    )->isNotEmpty();
                }
            )->pluck('name', 'id');

            $stores = Store::all()->filter(
                function (Store $store) {
                    return $store->usersWithReservationRights
                            ->where(
                                'id',
                                \Auth::id()
                            )->isNotEmpty()
                        || $store->usersWithOperationRights
                            ->where(
                                'id',
                                \Auth::id()
                            )->isNotEmpty();
                }
            )->pluck('name', 'id');
            $currencies = Currency::all()->pluck('name', 'id');

            //Флаг вывода checkbox и статуса disabled
            $pay = true;

            return view(
                'route-lists.pay',
                compact(
                    'collection',
                    'routeList',
                    'cashboxes',
                    'stores',
                    'currencies',
                    'pay'
                )
            );
        } else {
            return back();
        }
    }

    /**
     * Отображение формы редактирования маршрутного листа
     *
     * @param RouteList $routeList
     * @return Response
     */
    public function edit(Request $request, RouteList $routeList)
    {
        $orderDetails = collect();
        //Флаг вывода checkbox и статуса disabled
        $pay = false;
        $routeList->orders()->each(
            function (Order $order) use (&$orderDetails) {
                $orderDetails = $orderDetails->merge($order->orderDetails);
            }
        );

        $routeList->productReturns()->each(
            function (ProductReturn $productReturn) use (&$orderDetails) {
                $orderDetails = $orderDetails->merge($productReturn->orderDetails);
            }
        );

        $routeList->productExchanges()->each(
            function (ProductExchange $productExchange) use (&$orderDetails) {
                $orderDetails = $orderDetails->merge($productExchange->orderDetails);
                $orderDetails = $orderDetails->merge($productExchange->exchangeOrderDetails);
            }
        );

        $orderDetailsByState = $orderDetails->groupBy(
            function (OrderDetail $orderDetail) {
                return $orderDetail->currentState()->name;
            }
        );

        $pointTypes = RoutePoint::select('point_object_type')->groupBy('point_object_type')->get();
        $types[] = __('Not chosen');
        foreach ($pointTypes as $pointType) {
            switch ($pointType->point_object_type) {
                case 'App\Order': $types['App\Order'] = 'Заказ'; break;
                case 'App\ProductExchange': $types['App\ProductExchange'] = 'Обмен'; break;
                case 'App\ProductReturn': $types['App\ProductReturn'] = 'Возврат'; break;
                case 'App\CourierTask': $types['App\CourierTask'] = 'Задача для курьера'; break;
            }
        }

        $pointStates = RoutePointState::pluck('name', 'id')->prepend(__('Not chosen'), 0);

        $orderStates = \Auth::user()->getOrderStatesByRole();

        $pointObjectStates['Заказы'] = TripleFilterService::addObjectTypeToValue($orderStates, Order::class);
        $pointObjectStates['Обмены'] = TripleFilterService::addObjectTypeToValue(ProductExchangeState::pluck('name', 'id')->toArray(),ProductExchange::class);
        $pointObjectStates['Возвраты'] = TripleFilterService::addObjectTypeToValue(ProductReturnState::pluck('name', 'id')->toArray(),ProductReturn::class);
        $pointObjectStates['Задачи для курьеров'] = TripleFilterService::addObjectTypeToValue(CourierTaskState::pluck('name', 'id')->toArray(),CourierTask::class);

        $courier = $routeList->courier;

        if ($request->type) {
            $collection = $routeList->routePoints->where('point_object_type', $request->type);
        } elseif ($request->states && ((bool) array_filter($request->states))) {
            $collection = collect();
            $states = TripleFilterService::parseRequestInputFilter($request->states);
            foreach ($states as $object => $objectStateIds) {
                $collection->push($routeList->routePoints->where('point_object_type', $object)->filter(function ($value) use ($objectStateIds) {
                    return in_array($value->pointObject->currentState()->id, $objectStateIds);
                }));
            }
            $collection = $collection->collapse();
        } else {
            $collection = $routeList->routePoints;
        }

        if ($request->address) {
            $address = $request->address;
            $collection = $collection->filter(function ($value) use ($address) {
                return false !== stripos($value->pointObject->delivery_address, $address);
            });
        }

        if($request->date) {
            $date = $request->date;
            $collection = $collection->filter(function ($value) use ($date){
                return $value->pointObject->date_estimated_delivery == $date;
            });
        }

        if($request->pointState) {
            $pointState = $request->pointState;
            $collection = $collection->filter(function ($value) use ($pointState) {
                return $value->currentState()->id == $pointState;
            });
        }

        $collection = $collection->sortBy('id')->reverse();

        $paginatedItems = $this->paginate($collection, 15);
        $paginatedItems->setPath($request->url());

        return view(
            'route-lists.edit',
            compact(
                'routeList',
                'courier',
                'orderDetailsByState',
                'types',
                'paginatedItems',
                'pointStates',
                'pointObjectStates',
                'pay'
            )
        );
    }

    /**
     * @param $items
     * @param int $perPage
     * @param null $page
     * @param array $options
     * @return LengthAwarePaginator
     */
    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    /**
     * Сохранение данных из формы редактирования маршрутного листа
     *
     * @param RouteListRequest $request
     * @param RouteList $routeList
     * @return Response
     * @throws \Exception
     */
    public function update(RouteListRequest $request, RouteList $routeList)
    {
        $this->validate($request, [
            'store_id' => 'integer|required',
            'accepted_funds' => 'numeric|required',
            'costs' => 'numeric|required',
            'currency_id' => 'integer|required',
            'cashbox_id' => 'integer|required',
            'point_object_state' => 'array|required',
            'point_objects' => 'array|required',
            'order_id' => 'array|required',
            'order_detail_state' => 'array|required',
            'point_cashboxes' => 'array',
        ]);

        //не надо давать закрывать сам маршрутник, пока задача не выполнена
        if(in_array(CourierTask::class, $request->order_id)) {
            $courierTask = CourierTask::find(array_search(CourierTask::class, $request->order_id));
            if($courierTask->currentState()->is_new || $courierTask->currentState()->is_courier_state) {
                return back()->withErrors([__('Courier task must be close')]);
            }
        }

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        Config::set('exceptions.doing.throw', false);

        try {
             Config::set(
                    "cashbox.accumulator.currency.{$request->currency_id}",
                    $request->accepted_funds + $request->costs
                );

                $pointObjects = $request->point_objects;
                $pointCashboxes = $request->point_cashboxes;

            $collection = collect();
            foreach ($pointObjects as $key => $point) {
                $collection->push(RoutePoint::find($key));
            }

            $collection->each(
                    function (RoutePoint $routePoint) use ($request, $pointCashboxes) {
                        $pointObjectCashbox = isset($pointCashboxes[$routePoint->id]) && isset($pointCashboxes[$routePoint->id]['is_own_cashbox']) ?
                            Cashbox::find($pointCashboxes[$routePoint->id]['cashbox_id'])
                            :
                            Cashbox::find($request->cashbox_id);

                        Config::set(
                            "cashbox.accumulator.cashboxes.{$routePoint->point_object_type}.{$routePoint->point_object_id}",
                            $pointObjectCashbox
                        );
                    }
                );

            foreach ($request->order_id as $key => $order) {
                switch ($order) {
                    case 'App\Order':
                        $updatingOrder = Order::find($key);
                        foreach ($updatingOrder->orderDetails as $orderDetail) {
                            if($orderDetail->currentState()->is_returned) {
                                $orderDetail->update(['store_id' => $request->store_id]);
                            }
                        }

                        if($updatingOrder->getNextOrderStateDependingOrderDetailStates()) {
                            $updatingOrder->states()->save($updatingOrder->getNextOrderStateDependingOrderDetailStates());
                        }
                        break;
                    case 'App\ProductExchange':
                        $updatingProductExchange = ProductExchange::find($key);
                        foreach ($updatingProductExchange->orderDetails as $orderDetail) {
                            if($orderDetail->currentState()->is_returned) {
                                $orderDetail->update(['store_id' => $request->store_id]);
                            }
                        }
                        if($updatingProductExchange->getNextExchangeStateDependingOrderDetailStates()) {
                            $updatingProductExchange->states()->save($updatingProductExchange->getNextExchangeStateDependingOrderDetailStates());
                        }
                        break;
                    case 'App\ProductReturn':
                        $updatingProductReturn = ProductReturn::find($key);
                        foreach ($updatingProductReturn->orderDetails as $orderDetail) {
                            if($orderDetail->currentState()->is_returned) {
                                $orderDetail->update(['store_id' => $request->store_id]);
                            }
                        }
                        if($updatingProductReturn->getNextReturnStateDependingOrderDetailStates()) {
                            $updatingProductReturn->states()->save($updatingProductReturn->getNextReturnStateDependingOrderDetailStates());
                        }
                        break;
                }
            }

        } catch (\Exception $exception) {

            if (!($exception instanceof DoingException)) {
                DB::rollback();
                throw $exception;
            }

        }

        if (count(Config::get('exceptions.doing.messages'))) {

            DB::rollback();
            throw ValidationException::withMessages(Config::get('exceptions.doing.messages'));
        }

        $accumulator = Config::get("cashbox.accumulator.currency.{$request->currency_id}", false);

        if ($accumulator) {
            DB::rollback();
            throw ValidationException::withMessages(
                [
                    __(
                        'The revealed surpluses in foreign currency ":currency" for the total amount: :cost',
                        [
                            'currency' => Currency::find($request->currency_id)->name,
                            'cost' => $accumulator,
                        ]
                    ),
                ]
            );
        }

        $routePointList = '';
        foreach ($pointCashboxes as $key => $value) {
            $routePointList .= $key . ', ';
        }
        $routePointList = substr($routePointList, 0, -2);

        if ($accumulator !== false && $request->costs) {
            Operation::create(
                [
                    'type' => 'C',
                    'quantity' => $request->costs,
                    'operable_type' => Currency::class,
                    'operable_id' => $request->currency_id,
                    'storage_type' => Cashbox::class,
                    'storage_id' => $request->cashbox_id,
                    'user_id' => \Auth::id(),
                    'comment' => __('Transport costs on the route point')." {$routePointList}",
                ]
            )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);
        }


        Config::set('exceptions.doing.throw', true);

        DB::commit();

        return redirect()->route('route-lists.edit', $routeList)->with(
            'success',
            __('Route list updated successfully')
        );
    }

    /**
     * Поиск через API спутник
     *
     * @param $address
     * @return mixed
     */
    public function search($address)
    {
        $result = Curl::to('http://search.maps.sputnik.ru/search/addr?q=' . $address)
            ->asJson(true)
            ->get();

        if($result) {
            return $result;
        }
    }

    /**
     * Отображение страницы массового управления маршрутными листами
     *
     * @param Request $request
     * @return Response
     */
    public function manage(Request $request)
    {
        set_time_limit(0);
        $date = $request->date_estimated_delivery ?: Carbon::now()->format('d-m-Y');

        if($request->city_id) {
            $city = City::find($request->city_id);
        } else {
            $city = City::first();
        }

        $internalCarriersId = Carrier::where('city_id', $city->id)->get()->pluck('id');

        $orders = Order::all()
            ->where('is_hidden', 0)
            ->where('date_estimated_delivery', $date)
            ->whereIn('carrier_id', $internalCarriersId)
            ->filter(
                function (Order $order) {
                    return $order->active();
                }
            )
            ->sortBy('delivery_start_time')
            ->map(
                function (Order $order) {
                    $order->mapGeoCode = MapGeoCode::firstOrCreate(['hash' => md5($order->getMapDeliveryAddress())]);
                    $order->mapGeoCode->setIfEmpty($order->getMapDeliveryAddress());
                    return $order;
                }
            )
            ->groupBy(
                function (Order $order) {
                    return !is_null($order->routeList()) ? $order->routeList()->id : 0;
                }
            )
            ->sortKeys();

        $courierTasks = CourierTask::all()
            ->where('date', $date)
            ->where('is_done', 0)
            ->sortBy('start_time')
            ->filter(function (CourierTask $courierTask) use ($city){
                    return $courierTask->city_id == $city->id && $courierTask->currentState()->is_new;
            })
            ->map(function (CourierTask $courierTask) {
                $courierTask->mapGeoCode = MapGeoCode::firstOrCreate(['hash' => md5($courierTask->getMapDeliveryAddress())]);
                $courierTask->mapGeoCode->setIfEmpty($courierTask->getMapDeliveryAddress());
                return $courierTask;
            })
            ->groupBy(
                function (CourierTask $courierTask) {
                    return !is_null($courierTask->routeList()) ? $courierTask->routeList()->id : 0;
                }
            )
            ->sortKeys();

        $productReturns = ProductReturn::all()
            ->where('delivery_estimated_date', $date)
            ->whereIn('carrier_id', $internalCarriersId)
            ->filter(
                function (ProductReturn $productReturn) {
                    return !$productReturn->isCheckedPayment() && $productReturn->active();
                }
            )
            ->sortBy('delivery_start_time')
            ->map(
                function (ProductReturn $productReturn) {
                    $productReturn->mapGeoCode = MapGeoCode::firstOrCreate(['hash' => md5($productReturn->getMapDeliveryAddress())]);
                    $productReturn->mapGeoCode->setIfEmpty($productReturn->getMapDeliveryAddress());
                    return $productReturn;
                }
            )
            ->groupBy(
                function (ProductReturn $productReturn) {
                    return !is_null($productReturn->routeList()) ? $productReturn->routeList()->id : 0;
                }
            )
            ->sortKeys();

        $productExchanges = ProductExchange::all()
            ->where('delivery_estimated_date', $date)
            ->whereIn('carrier_id', $internalCarriersId)
            ->filter(
                function (ProductExchange $productExchange) {
                    return !$productExchange->isCheckedPayment() && $productExchange->active();
                }
            )
            ->sortBy('delivery_start_time')
            ->map(
                function (ProductExchange $productExchange) {
                    $productExchange->mapGeoCode = MapGeoCode::firstOrCreate(['hash' => md5($productExchange->getMapDeliveryAddress())]);
                    $productExchange->mapGeoCode->setIfEmpty($productExchange->getMapDeliveryAddress());
                    return $productExchange;
                }
            )
            ->groupBy(
                function (ProductExchange $productExchange) {
                    return !is_null($productExchange->routeList()) ? $productExchange->routeList()->id : 0;
                }
            )
            ->sortKeys();

        $courierRoles = Role::query()->where('is_courier', 1)->get();

        $routeLists = RouteList::all()
            ->filter(function (RouteList $routeList){
                return !$routeList->courier->is_not_working;
            })
            ->map(
                function (RouteList $routeList) {
                    return collect(
                        [
                            'id' => "RL-{$routeList->id}",
                            'name' => "{$routeList->id} - {$routeList->courier->name} - {$routeList->date_list}",
                            'color' => $routeList->courier->color,
                        ]
                    );
                }
            );

        User::all()
            ->filter(
                function (User $user) use ($courierRoles) {
                    return $user->hasAnyRole($courierRoles) && !$user->is_not_working;
                }
            )
            ->each(
                function (User $user) use ($date, $routeLists) {
                    if(isset($user->routeList->id)){
                        $routeLists->push(
                            collect(
                                [
                                    'id' => "RL-{$user->routeList->id}",
                                    'name' => $user->name,
                                    'color' => $user->color,
                                ]
                            )
                        );
                    }                
                }
            );

        $routeListsColor = $routeLists
            ->pluck('color', 'id')
            ->map(
                function ($item) {
                    return ['data-color' => $item];
                }
            );

        $routeLists = $routeLists
            ->pluck('name', 'id')
            ->prepend(__('Unknown'), 'RL-0');

        return view(
            'route-list-manage.index',
            compact(
                'date',
                'orders',
                'productReturns',
                'productExchanges',
                'routeLists',
                'routeListsColor',
                'courierTasks',
                'city'
            )
        );

    }

    /**
     * @param RouteList $routeList
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function courierLeft(RouteList $routeList)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();
        $internalCarriersId = Carrier::where('is_internal', 1)->get()->pluck('id');


        try {
            $orders = $routeList->orders()
                ->where('is_hidden', 0)
                ->where('date_estimated_delivery', Carbon::now()->format('d-m-Y'))
                ->filter(
                    function (Order $order) {
                        return $order->active() && $order->shipAvailable();
                    }
                );
            foreach ($orders as $order) {
                if(!$order->currentState()->is_sent) {
                    while (!$order->currentState()->is_sent) {
                        $newState = $order->nextStates()->where('is_sent', '=', 1)->first();
                        if ($newState) {
                            $order->states()->save($newState);
                        } else {
                            $newState = $order->nextStates()->where('next_auto_closing_status', '=', 1)->first();
                            $order->states()->save($newState);
                        }
                    }
                }
            }

        $productExchanges = $routeList->productExchanges()
            ->where('delivery_estimated_date', Carbon::now()->format('d-m-Y'))
            ->filter(
                function (ProductExchange $productExchange) {
                    return $productExchange->active() && $productExchange->shipAvailable();
                }
            );

        foreach ($productExchanges as $productExchange) {
            if (!$productExchange->currentState()->is_sent) {
                while (!ProductExchange::find($productExchange->id)->currentState()->is_sent) {
                    $productExchange = ProductExchange::find($productExchange->id);
                    $newSentState = $productExchange->nextStates()->where('is_sent', '=', 1)->first();
                    if ($newSentState) {
                        $productExchange->states()->save($newSentState);
                    } else {
                        $newState = $productExchange->nextStates()->where('next_auto_closing_status', '=', 1)->first();
                        $productExchange->states()->save($newState);
                    }
                }
            }
        }

        DB::commit();
        } catch (\Exception $exception) {
            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }
        }

        return back();
    }

    /**
     * Сохранение данных из формы со страницы массового управления маршрутными листами
     *
     * @param RouteListManageRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function manageUpdate(RouteListManageRequest $request)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();
        try {
            //TODO Какая-то "колбаса" ниже опять получилась. Надо разбить.

            $courierTasksRouteLists = collect($request->courier_tasks_route_lists)
                ->filter(function ($item) {
                    return (bool)strstr($item, 'RL-');
                })
                ->map(function ($item) {
                    return str_replace('RL-', '', $item);
                })->toArray();

            foreach ($courierTasksRouteLists as $courierTaskId => $courierTasksRouteListId) {
                $courierTask = CourierTask::find($courierTaskId);

                if($courierTaskId == 0 && !is_null($courierTask->routeList())) {
                    $courierTask->routePoints()
                        ->where('route_list_id', $courierTask->routeList()->id)
                        ->each(function (RoutePoint $routePoint){
                            $routePoint->update(['is_point_object_attached' => 0]);
                        });
                } elseif ($courierTasksRouteListId > 0) {
                    if($courierTask->routeList() && ($courierTask->routeList()->id != $courierTasksRouteListId)) {
                        $routePoint = $courierTask->routePoints()->where('is_point_object_attached', 1)->first();
                        $routePoint->update([
                            'route_list_id' => $courierTasksRouteListId,
                        ]);
                    } else {
                        RoutePoint::firstOrCreate(
                            [
                                'route_list_id' => $courierTasksRouteListId,
                                'point_object_type' => CourierTask::class,
                                'point_object_id' => $courierTask->id,
                            ]
                        )
                            ->update(['is_point_object_attached' => 1]);
                    }
                }
            }

            $ordersRouteLists = collect($request->orders_route_lists)
                ->filter(
                    function ($item) {
                        return (bool)strstr($item, 'RL-');
                    }
                )->map(
                    function ($item) {
                        return str_replace('RL-', '', $item);
                    }
                )->toArray();
            foreach ($ordersRouteLists as $orderId => $ordersRouteListId) {
                $order = Order::find($orderId);

                if ($ordersRouteListId == 0 && !is_null($order->routeList())) {
                    $order
                        ->routePoints()
                        ->where('route_list_id', $order->routeList()->id)
                        ->each(
                            function (RoutePoint $routePoint) {
                                $routePoint->delete();
                            }
                        );
                } elseif ($ordersRouteListId > 0) {
                    if($order->routeList() && ($order->routeList()->id != $ordersRouteListId)) {
                        $routePoint = $order->routePoints()->where('is_point_object_attached', 1)->first();
                        $routePoint->update(
                            [
                                'route_list_id' => $ordersRouteListId,
                            ]
                        );
                    } else {
                        RoutePoint::firstOrCreate(
                            [
                                'route_list_id' => $ordersRouteListId,
                                'point_object_type' => Order::class,
                                'point_object_id' => $order->id,
                            ]
                        )
                            ->update(['is_point_object_attached' => 1]);
                    }
                }
            }
            $productReturnsRouteLists = collect($request->product_returns_route_lists)
                ->filter(
                    function ($item) {
                        return (bool)strstr($item, 'RL-');
                    }
                )->map(
                    function ($item) {
                        return str_replace('RL-', '', $item);
                    }
                )->toArray();
            foreach ($productReturnsRouteLists as $productReturnId => $productReturnsRouteListId) {
                $productReturn = ProductReturn::find($productReturnId);

                if ($productReturnsRouteListId == 0 && !is_null($productReturn->routeList())) {

                    $productReturn
                        ->routePoints()
                        ->where('route_list_id', $productReturn->routeList()->id)
                        ->each(
                            function (RoutePoint $routePoint) {
                                $routePoint->update(['is_point_object_attached' => 0]);
                            }
                        );

                } elseif ($productReturnsRouteListId > 0) {
                    if($productReturn->routeList() && ($productReturn->routeList()->id != $productReturnsRouteListId)) {
                        $routePoint = $productReturn->routePoints()->where('is_point_object_attached', 1)->first();
                        $routePoint->update(
                            [
                                'route_list_id' => $productReturnsRouteListId,
                            ]
                        );
                    } else {
                        RoutePoint::firstOrCreate(
                            [
                                'route_list_id' => $productReturnsRouteListId,
                                'point_object_type' => ProductReturn::class,
                                'point_object_id' => $productReturn->id,
                            ]
                        )
                            ->update(['is_point_object_attached' => 1]);
                    }
                }
            }
            $productExchangesRouteLists = collect($request->product_exchanges_route_lists)
                ->filter(
                    function ($item) {
                        return (bool)strstr($item, 'RL-');
                    }
                )->map(
                    function ($item) {
                        return str_replace('RL-', '', $item);
                    }
                )->toArray();
            foreach ($productExchangesRouteLists as $productExchangeId => $productExchangesRouteListId) {
                $productExchange = ProductExchange::find($productExchangeId);

                if ($productExchangesRouteListId == 0 && !is_null($productExchange->routeList())) {

                    $productExchange
                        ->routePoints()
                        ->where('route_list_id', $productExchange->routeList()->id)
                        ->each(
                            function (RoutePoint $routePoint) {
                                $routePoint->update(['is_point_object_attached' => 0]);
                            }
                        );

                } elseif ($productExchangesRouteListId > 0) {
                    if($productExchange->routeList() && ($productExchange->routeList()->id != $productExchangesRouteListId)) {
                        $routePoint = $productExchange->routePoints()->where('is_point_object_attached', 1)->first();
                        $routePoint->update(
                            [
                                'route_list_id' => $productExchangesRouteListId,
                            ]
                        );
                    } else {
                        RoutePoint::firstOrCreate(
                            [
                                'route_list_id' => $productExchangesRouteListId,
                                'point_object_type' => ProductExchange::class,
                                'point_object_id' => $productExchange->id,
                            ]
                        )
                            ->update(['is_point_object_attached' => 1]);
                    }
                }
            }
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
            ->route('route-list-manage.index', ['date_estimated_delivery' => $request->date, 'city_id' => $request->city_id])
            ->with('success', __('Route lists updated successfully'));

    }

    /**
     * Смена статуса товарной позиции из маршрутного листа
     *
     * @param OrderDetail $orderDetail
     * @param OrderDetailState $orderDetailState
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function action(OrderDetail $orderDetail, OrderDetailState $orderDetailState)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {
            $orderDetail->states()->save($orderDetailState);

        } catch (\Exception $exception) {
            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }


        DB::commit();

        /**
         * @var RoutePoint $routePoint
         */
        $routePoint = $orderDetail
            ->owner()
            ->routePoints()
            ->first();

        $cashboxes = Cashbox::all()->filter(
            function (Cashbox $cashbox) use ($routePoint) {

                $routeList = $routePoint->routeList;

                return $cashbox->users->where(
                        'id',
                        \Auth::id()
                    )->isNotEmpty();
            }
        )->pluck('name', 'id');

        //Флаг вывода checkbox и статуса disabled
        $pay = true;

        return response()->json(
            [
                'okState' => $orderDetail->currentState()->name,
                'html' => view(
                    'route-lists._partials.route-point.row',
                    compact('routePoint','pay', 'cashboxes')
                )->render(),
            ]
        );

    }

    /**
     * Смена статуса объекта маршрутной точки из маршрутного листа
     *
     * @param RoutePoint $routePoint
     * @param int $pointObjectState
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function actionRoutePoint(RoutePoint $routePoint, $pointObjectState, Store $store)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            /**
             * @var Order|ProductReturn|ProductExchange $pointObject
             */
            $pointObject = $routePoint->pointObject;

            switch ($routePoint->point_object_type) {
                case Order::class:
                    foreach ($pointObject->orderDetails as $orderDetail) {
                        if (!is_null($store)) {
                            $orderDetail->store()->associate($store)->save();
                        }
                    }
                    $pointObject->states()->save(OrderState::find((int)$pointObjectState));
                    break;
                case ProductReturn::class:
                    foreach ($pointObject->orderDetails as $orderDetail) {
                        if (!is_null($store)) {
                            $orderDetail->store()->associate($store)->save();
                        }
                    }
                    $pointObject->states()->save(ProductReturnState::find((int)$pointObjectState));
                    break;
                case ProductExchange::class:
                    foreach ($pointObject->orderDetails as $orderDetail) {
                        if (!is_null($store)) {
                            $orderDetail->store()->associate($store)->save();
                        }
                    }
                    foreach ($pointObject->exchangeOrderDetails as $orderDetail) {
                        if (!is_null($store)) {
                            $orderDetail->store()->associate($store)->save();
                        }
                    }
                    $pointObject->states()->save(ProductExchangeState::find((int)$pointObjectState));
                    break;
                case CourierTask::class:
                    $pointObject->states()->save(CourierTaskState::find((int)$pointObjectState));
                    break;
            }

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }


        DB::commit();

        $routePoint = $routePoint->refresh();
        $pointObject = $pointObject->refresh();

        $cashboxes = Cashbox::all()->filter(
            function (Cashbox $cashbox) use ($routePoint) {

                $routeList = $routePoint->routeList;

                return $cashbox->users->where(
                    'id',
                    \Auth::id()
                )->isNotEmpty();
            }
        )->pluck('name', 'id');

        //Флаг вывода checkbox и статуса disabled
        $pay = true;
        if(get_class($pointObject) == \App\CourierTask::class) {
            $okState = $pointObject->is_done ? __('Done') : __('Don`t Done');
        } else {
            $okState = $pointObject->currentState()->name;
        }

        return response()->json(
            [
                'okState' => $okState,
                'html' => view(
                    'route-lists._partials.route-point.row',
                    compact('routePoint','pay', 'cashboxes' )
                )->render(),
            ]
        );

    }

    /**
     * Смена маршрутного лисат объекта маршрутной точки
     *
     * @param RoutePoint $routePoint
     * @param int $pointObjectRouteList
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function actionRoutePointObject(RoutePoint $routePoint, $pointObjectRouteList)
    {
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            /**
             * @var Order|ProductReturn|ProductExchange $pointObject
             */
            $pointObject = $routePoint->pointObject;

            if ($pointObjectRouteList == 0 && !is_null($pointObject->routeList())) {

                $pointObject
                    ->routePoints()
                    ->where('route_list_id', $pointObject->routeList()->id)
                    ->each(
                        function (RoutePoint $routePoint) {
                            $routePoint->update(['is_point_object_attached' => 0]);
                        }
                    );

            } elseif ($pointObjectRouteList > 0) {

                RoutePoint::firstOrCreate(
                    [
                        'route_list_id' => $pointObjectRouteList,
                        'point_object_type' => $routePoint->point_object_type,
                        'point_object_id' => $pointObject->id,
                    ]
                )
                    ->update(['is_point_object_attached' => 1]);

            }

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }


        DB::commit();


        $routePoint = RoutePoint::find($routePoint->id);

        if (!is_null($routePoint)) {
            $cashboxes = Cashbox::all()->filter(
                function (Cashbox $cashbox) use ($routePoint) {

                    $routeList = $routePoint->routeList;

                    return $cashbox->users->where(
                            'id',
                            \Auth::id()
                        )->isNotEmpty() || (!is_null($routeList->cashbox) && !$routeList->isEditable(
                            ) && $routeList->cashbox->id == $cashbox->id);
                }
            )->pluck('name', 'id');
        }

        return response()->json(
            [
                'okState' => 'true',
                'html' => is_null($routePoint) ?
                    ''
                    :
                    view(
                        'route-lists._partials.route-point.row',
                        compact('routePoint', 'cashboxes')
                    )->render(),
            ]
        );

    }

    public function actionOwnTask(CourierTask $courierTask, CourierTaskState $courierTaskState)
    {
        if($courierTask->routeList() && $courierTask->routeList()->courier_id !== \Auth::id() || $courierTask->routeList()->courier->is_not_working) {
            throw UnauthorizedException::forPermissions([]);
        }

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {
            $courierTask->states()->save($courierTaskState);
        } catch (\Exception $exception) {
            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }
        }

        DB::commit();

        $nextStates = $courierTask->nextStates()->where('is_courier_state', '=', 1)->pluck('name', 'id');

        return response()->json(
            [
                'okState' => preg_replace('/\s+\(.+\)/', '', $courierTask->currentState()->name),
                'nextStates' => view(
                    'route-lists-own.buttons.courier-task',
                    compact('nextStates', 'courierTask')
                )->render(),
            ]
        );
    }

    /**
     * Смена статуса товарной позиции из собственного маршрутного листа
     * @param OrderDetail $orderDetail
     * @param OrderDetailState $orderDetailState
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function actionOwn(OrderDetail $orderDetail, OrderDetailState $orderDetailState)
    {

        if (($orderDetail->order->routeList() && $orderDetail->order->routeList()->courier_id !== \Auth::id())
            || ($orderDetail->productReturn
                && $orderDetail->productReturn->routeList()->courier_id !== \Auth::id())
            || ($orderDetail->productExchange
                && $orderDetail->productExchange->routeList()->courier_id !== \Auth::id()
            || $orderDetail->order->routeList()->courier->is_not_working)
        ) {
            throw UnauthorizedException::forPermissions([]);
        }

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $orderDetail->states()->save($orderDetailState);

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }


        DB::commit();

        $nextStates = $orderDetail->nextStates()->where('is_courier_state', '=', 1)->where('is_hidden', '=', 0)->where(
            'owner_type',
            $orderDetail->owner_type
        )->pluck('name', 'id');

        return response()->json(
            [
                'okState' => preg_replace('/\s+\(.+\)/', '', $orderDetail->currentState()->name),
                'nextStates' => view(
                    'route-lists-own.buttons.order-detail',
                    compact('nextStates', 'orderDetail')
                )->render(),
            ]
        );

    }
}
