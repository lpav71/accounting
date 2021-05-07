<?php

namespace App\Http\Controllers;

use App;
use App\Carrier;
use App\Cashbox;
use App\Certificate;
use App\Channel;
use App\Currency;
use App\Customer;
use App\Operation;
use App\Substitute;
use App\Order;
use App\OrderDetail;
use App\OrderState;
use App\Product;
use App\RouteListState;
use App\Services\CalculateExpenseService\CalculateExpenseService;
use App\Services\ThermalPrinter\ThermalPrinter;
use App\Store;
use App\User;
use App\Utm;
use App\UtmCampaign;
use App\UtmSource;
use App\Task;
use App\OrderDetailState;
use App\Filters\OrderFilter;
use App\RuleOrderPermission;
use App\Http\Requests\OrderRequest;
use Carbon\Carbon;
use Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Sabberworm\CSS\Rule\Rule;
use Validator;
use DB;
use App\Exceptions\DoingException;
use App\FastMessageTemplate;
use Unirest;
use App\Services\Messenger\Classes\SmsMessenger;
use App\Services\Messenger\StaticFactory;
use App\Http\Resources\OrderResources;

//TODO Контроллер требует пересмотра с точки зрения оптимизации запросов и разбиения на методы
class OrderController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:order-list', ['except' => ['ajaxStore']]);
        $this->middleware('permission:order-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:order-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список заказов за исключением скрытых
     *
     * @var \App\Order $order
     * @var \App\Filters\OrderFilter $filters
     * @var \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Order $order, OrderFilter $filters, Request $request)
    {
        $currentUser = auth()->user();
        $currentRoles = $currentUser->roles;
        $orderStatesArr = array();
        $orderCarrier = array();
        $rules = RuleOrderPermission::all();
        foreach ($rules as $rule) {
            $users = $rule->user;
            foreach ($users as $user)
            {
                if ($currentUser->id == $user->id)
                {
                    //Формирование массива запрещённых магазинов ----------------------------------------------
                    $orderStatesArr = $rule->orderState->pluck('id', 'id');

                    //Формирование массива запрещённых служб доставки -----------------------------------------
                    $orderCarrier = $rule->carrier->pluck('id', 'id');
                }
            }
            $roles = $rule->role;
            $currentRoles = $currentRoles->toArray();
            foreach ($roles as $role)
            {
                foreach ($currentRoles as $currentRole)
                {
                    if ($currentRole['id'] == $role->id)
                    {
                        //Формирование массива запрещённых магазинов ----------------------------------------------
                        $orderStatesArr = $rule->orderState->pluck('id', 'id')->toArray();

                        //Формирование массива запрещённых служб доставки -----------------------------------------
                        if ($rule->is_carrier == 1 )  //Если именно служба
                        $orderCarrier = $rule->carrier->pluck('id', 'id')->toArray();

                        //Формирование массива запрещённых групп служб доставки -----------------------------------
                        if ($rule->is_carrier == 0 )  //Если именно группа служб
                        {
                            $carrierGroup = $rule->carrierGroup;
                            foreach ($carrierGroup as $carrier)
                            {
                                $carr = Carrier::where('carrier_group_id', $carrier->id)->pluck('id', 'id')->toArray();
                                $orderCarrier = array_merge($orderCarrier, $carr);
                            }
                        }
                    }
                }
            }
        }
        $filters->default(['prohibition']);
        $orders = $order
            ->where('is_hidden', 0)
            ->filter($filters)
            ->sortable(['id' => 'desc'])
            ->get();

        //count expired tasks
        $expiredTasks = Task::getExpiredTasks()->count();
        //count actual tasks
        $tasksActual = Task::getActualTasks()->count();


        //orders with need states
        $orderWithLastStates = OrderResources::OrderWithLastStates();

        $confirmedStateId = OrderState::where('is_confirmed', true)->first() !== null ? OrderState::where('is_confirmed', true)->first()->id : false;
        if($confirmedStateId){
            $confirmedOrders = $orderWithLastStates
            ->where('order_order_state.order_state_id', $confirmedStateId)
            ->count();
        }else{
            $confirmedOrders = __("Mark 'confirmed' order state");
        }
        
        //count red orders
        $redClients = OrderResources::CountRedOrders();
        
        $selfShipping = Order::where('is_hidden', 0)
            ->whereIn('carrier_id', Carrier::where('self_shipping', 1)->pluck('id'))
            ->where('date_estimated_delivery', Carbon::now()->toDateString())
            ->get()
            ->filter(function (Order $order) {
                return !$order->isFinished();
            })
            ->count();
        $orderStates = \Auth::user()->getOrderStatesByRole();
        $orderStates[0] = '--';
        ksort($orderStates);

        $ordersFilter = collect([]);
        foreach ($orders as $order)
        {
            if(!in_array($order->currentState()->id, $orderStatesArr))  //Если статус присутствует в массиве
            {
                if (isset($order->carrier()->get()[0]->id))  //Если служба существует
                {
                    if(!in_array($order->carrier()->get()[0]->id, $orderCarrier))  //Если служба присутствует в массиве
                        $ordersFilter->push($order);  //тогда добавляем запись в выходную коллекцию
                }
            }
        }
        $ordersFilter = $ordersFilter->paginate(25)->appends($request->query());
        $orders = $ordersFilter;
        return view('orders.index', compact('orders', 'redClients', 'expiredTasks', 'tasksActual', 'confirmedOrders', 'orderStates','selfShipping'));
    }

    /**
     * Отображает список скрытых заказов
     *
     * @var \App\Order $order
     * @var \App\Filters\OrderFilter $filters
     * @var \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function hiddenIndex(Order $order, OrderFilter $filters, Request $request)
    {
        $orders = $order
            ->where('is_hidden', 1)
            ->filter($filters)
            ->sortable(['id' => 'desc'])
            ->paginate(25)
            ->appends(
                $request->query()
            );

        $orderStates = \Auth::user()->getOrderStatesByRole();
        $orderStates[0] = '--';
        ksort($orderStates);

        return view('orders.index', compact('orders', 'orderStates'));
    }

    /**
     * Список заказов из отчета по расходам
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function expenseOrders(Request $request)
    {
        $request->validate(['orders' => 'required|array']);

        $orders = Order::whereIn('id', $request->orders)->paginate(15)->appends($request->query());
        $orderStates = \Auth::user()->getOrderStatesByRole();
        $orderStates[0] = '--';
        ksort($orderStates);

        return view('orders.expense-index', compact('selfShipping','orders', 'redClients', 'expiredTasks', 'tasksActual', 'confirmedOrders', 'orderStates'));
    }

    /**
     * Отображение формы для создания нового заказа
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $channels = Channel::where('is_hidden',0)->pluck('name', 'id');
        $orderDetailStartStates = OrderDetailState::where('is_hidden', 0)
            ->where('owner_type', Order::class)
            ->get()
            ->filter(
                function (
                    OrderDetailState $orderDetailState
                ) {
                    return !$orderDetailState->previousStates()->count();
                }
            )->pluck('name', 'id');
        $products = Product::all()->pluck('name', 'id');
        $currencies = Currency::all()->pluck('name', 'id');
        $stores = Store::all()->pluck('name', 'id');

        return view(
            'orders.create',
            compact(
                'channels',
                'orderDetailStartStates',
                'currencies',
                'stores',
                'products'
            )
        );
    }

    /**
     * Сохранение данных из формы создания нового заказа
     *
     * @param OrderRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->input(),
            [
                'first_name' => 'required|string|min:1',
                'email' => 'nullable|email',
                'phone' => 'required|regex:/^[\+]{0,1}[0-9\-\(\)\s]+$/|min:11',
                'channel_id' => 'required',
                'delivery_address_comment' => 'nullable|string',
                'order_detail_add' => 'array',
                'certificate_number' => 'int'
            ]
        );

        if ($validator->fails()) {
            return back()->withInput($request->input())->withErrors($validator->getMessageBag()->getMessages());
        }

        $phone = isset($request->phone) ? $request->phone : '';

        if (isset($request->phone)) {
            switch (substr($request->phone, 0, 1)) {
                case '8':
                    $phone = substr_replace($request->phone, '7', 0, 1);
                    break;
                case '9':
                    $phone = substr_replace($request->phone, '79', 0, 1);
                    break;
            }
        }

        $customerData = array_filter(
                [
                    'first_name' => $request->first_name,
                    'email' => $request->email,
                    'phone' => ($phone ? preg_replace(
                        '/[\s\+\(\)\-]/',
                        '',
                        $phone
                    ) : null),
                ],
                function ($item) {
                    return (bool)$item;
                }
            );

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $customer = Customer::firstOrCreate($customerData);

            $order = new Order();
            $order->customer_id = $customer->id;
            $order->delivery_address_comment = $request->delivery_address_comment;
            $order->channel_id = $request->channel_id;
            $order->save();
            $order->states()->save(OrderState::where('is_new',1)->first());
            $orderDetails = $request->order_detail_add;
            foreach ($orderDetails as $orderDetail) {
                $orderDetail['order_id'] = $order->id;
                $orderDetailObject = OrderDetail::create($orderDetail);
                $orderDetailObject->states()->save(
                    OrderDetailState::find($orderDetail['order_detail_state_id'])
                );
                if($orderDetailObject->product->category->is_certificate) {
                    Certificate::create([
                        'number' => null,
                        'order_detail_id' => $orderDetailObject->id
                    ]);
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

        return redirect()->route('orders.edit', $order->id)->with('success', 'Order created successfully');
    }

    /**
     * Отображение заказа
     *
     * @param  \App\Order $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        $orderStates = $order->states()->get();

        return view('orders.show', compact('order', 'orderStates'));
    }

    /**
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \ChrisKonnertz\StringCalc\Exceptions\ContainerException
     * @throws \ChrisKonnertz\StringCalc\Exceptions\NotFoundException
     */
    public function edit(Order $order, Request $request)
    {
        if ($request->api_edit) {
            $order->update(['is_new_api_order' => 0]);

            return redirect()->route('orders.edit', ['order' => $order]);
        }
        $customers = Customer::orderByDesc('id')->get()->pluck('full_name', 'id');

        $certificates = DB::table('certificates')->select(['certificates.id', 'certificates.number'])
            ->whereNotNull('number')
            ->leftJoin('order_details', 'certificates.order_detail_id', '=', 'order_details.id')
            ->leftJoin('orders', 'order_details.order_id', '=', 'orders.id')
            ->where('orders.channel_id', '=', $order->channel_id)
            ->leftJoin('order_order_state', 'orders.id', '=', 'order_order_state.order_id')
            ->where('order_order_state.order_state_id', '=', OrderState::where('is_successful', '=', 1)->first()->id)
            ->get()->pluck('number', 'id');

        $carriers = [];
        $carriers[] = __('Unknown');
        \Auth::user()->carrierGroups->each(function (App\CarrierGroup $carrierGroup) use (&$carriers) {
            foreach ($carrierGroup->carriers as $carrier) {
                $carriers[$carrier->id] = $carrier->name;
            }
        });
        if(!in_array($order->carrier_id, array_keys($carriers))) {
            $carriers[$order->carrier->id] = $order->carrier->name;
        }
        $users = User::all()->pluck('name', 'id')->prepend(__('Unknown'), '0');
        $channels = Channel::where('is_hidden',0)->pluck('name', 'id');
        $cdekStates = $order->cdekStates()->get();
        $roleOrderStates = \Auth::user()->getOrderStateIdsByRole();
        $orderStates = [];
        if(in_array($order->currentState()['id'], $roleOrderStates)){
        $orderStates = OrderState::all()->filter(
            function (OrderState $orderState) use ($order, $roleOrderStates) {
                return $orderState->previousStates()->where('id', $order->currentState()['id'])->count() && in_array($orderState->id, $roleOrderStates);
            }
        )->pluck('name', 'id')->prepend($order->currentState()['name'], $order->currentState()['id']);
        }else{
            $orderStates[$order->currentState()['id']] = $order->currentState()['name'];
        }
        $orderDetailStartStates = OrderDetailState::where('is_hidden', 0)
            ->where('owner_type', Order::class)
            ->get()
            ->filter(
                function (
                    OrderDetailState $orderDetailState
                ) {
                    return !$orderDetailState->previousStates()->count();
                }
            )
            ->pluck('name', 'id');
        $products = Product::all()->pluck('name', 'id');
        $currencies = Currency::all()->pluck('name', 'id');
        $stores = Store::all()->pluck('name', 'id');
        $cashboxOperations = $order->operations->where('storage_type', 'App\Cashbox');
        $tasks = Task::orWhere('order_id', $order->id)->orWhere('customer_id', $order->customer->id)->orderByDesc(
            'created_at'
        )->get();
        $orderComments = clone $order->comments;

        $orderHistory = collect([]);

        $orderHistory = $orderComments->reduce(function (Collection $result, App\OrderComment $orderComment) {
            $obj = new \stdClass();
            $obj->created_at = clone $orderComment->created_at;
            $obj->author = ($orderComment->user_id ? $orderComment->author->name : 'System');
            $obj->comment = $orderComment->comment;
            $obj->is_call = false;
            $obj->is_task = false;

            $matches = [];
            if (preg_match("/#task-([0-9]+)/", $orderComment->comment, $matches)) {
                $obj->is_task = true;
                $obj->task_id = (int)$matches[1];
            }

            $result->push($obj);


            return $result;
        }, $orderHistory);

        $customerCalls = $order->customer->calls();
        $orderHistory = $customerCalls->reduce(function (Collection $result, App\Services\Telephony\Interfaces\CallInterface $call) {
            $obj = new \stdClass();
            $obj->created_at = clone $call->created_at;
            $obj->author = ($call->isOutgoing() ? __('Outgoing') : __('Incoming'));
            $obj->comment = clone $call;
            $obj->is_call = true;
            $obj->is_task = false;
            $result->push($obj);

            return $result;
        }, $orderHistory);

        $orderHistory = $orderHistory->sortBy('created_at');

        $isAvailableReturn = $order
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
            )->isNotEmpty();

        //Расходы по заказу
        $service = new CalculateExpenseService();
        $expenses = $service->calculateOrder($order);
        //В редктировании заказа нет нужды отдельной прибыли
        if(!empty($expenses)) {
            $expenses = $expenses['expense'];
        }

        $smsTemplates = SmsMessenger::templates()
            ->get()
            ->filter(function (FastMessageTemplate $fastMessageTemplate) use ($order){
                foreach ($fastMessageTemplate->channels as $channel) {
                    if ($channel->id === $order->channel->id) {
                        return true;
                    }
                }
            });
            $messagesData['type'] = 'sms';
            $messagesData['messages'] = [];
            $smsTemplates->map(function(FastMessageTemplate $fastMessageTemplate) use (&$messagesData, $order){
                $messenger = StaticFactory::build('sms');
                $messageData['name'] = $fastMessageTemplate->name;
                $messageData['message'] = $messenger->setMessage($fastMessageTemplate->message)
                    ->fillReplacementsByOrder($order)
                    ->getMessage();

                $messageData['message'] = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $messageData['message']);
                $messagesData['messages'][] = $messageData;
            });
            $smsData = json_encode($messagesData, JSON_UNESCAPED_UNICODE);

        $cashboxes = Cashbox::all()->filter(
            function (Cashbox $cashbox) {
                return $cashbox->users->where(
                    'id',
                    \Auth::id()
                )->isNotEmpty();
            }
        )->pluck('name', 'id');
        
        $internalCarriersId = Carrier::where('is_internal', 1)->get()->pluck('id')->toArray();
        $courier = '';
        if(in_array($order->carrier_id, $internalCarriersId) && !empty($order->routeList())) {
            $courier = $order->routeList()->courier->name;
        }


        return view(
            'orders.edit',
            compact(
                'order',
                'customers',
                'carriers',
                'channels',
                'currencies',
                'users',
                'orderStates',
                'orderDetailStartStates',
                'products',
                'stores',
                'cashboxOperations',
                'tasks',
                'orderHistory',
                'isAvailableReturn',
                'cashboxes',
                'cdekStates',
                'expenses',
                'smsData',
                'courier',
                'certificates'
            )
        );
    }

    /**
     * Быстрое закрытие заказа
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function fastClose(Request $request)
    {
        $this->validate($request,[
            'cashbox_id' => 'integer|required',
            'order_id' => 'integer|required',
            'quantity' => 'numeric|required',
            'comment' => 'string|required'
        ]);

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        Config::set('exceptions.doing.throw', false);
        $currency = Currency::where('is_default', 1)->first();

        try {
            Config::set(
                "cashbox.accumulator.currency.{$currency->id}",
                $request->quantity
            );

            $order_id = $request->order_id;
            $cashbox_id = $request->cashbox_id;

            Config::set(
                "cashbox.accumulator.cashboxes.". Order::class . ".{$order_id}",
                Cashbox::find($cashbox_id)
            );

            //Находим нужный статус маршрутного листа для последовательности состояний
            $routeListState = RouteListState::where(['is_successful' => 1])->first();
            $updatingOrder = Order::find($order_id);

            foreach ($updatingOrder->orderDetails as $orderDetail) {
                if($orderDetail->currentState()->is_sent) {
                    $orderDetail->states()->save($orderDetail->nextStates()->where('is_delivered', 1)->first());
                }
            }

            $updatingOrder->states()->save($routeListState->newOrderState($updatingOrder->currentState()));

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

        $accumulator = Config::get("cashbox.accumulator.currency.{$currency->id}", false);

        if ($accumulator) {
            DB::rollback();
            throw ValidationException::withMessages(
                [
                    __(
                        'The revealed surpluses in foreign currency ":currency" for the total amount: :cost',
                        [
                            'currency' => $currency->name,
                            'cost' => $accumulator,
                        ]
                    ),
                ]
            );
        }

        $updatingOrder->comments()->create([
            'comment' => __('Comment to fast close order :comment', ['comment' => isset($request->comment) ? $request->comment : '']),
            'user_id' => \Auth::id() ?: null,
        ]);

        Config::set('exceptions.doing.throw', true);

        DB::commit();

        return back()->with(
            'success',
            __('Order updated successfully')
        );
    }

    /**
     * Сохранение данных из формы редактирования заказа
     *
     * @param OrderRequest $request
     * @param Order $order
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function update(OrderRequest $request, Order $order)
    {
        $finds = Substitute::all();
        foreach ($finds as $find)
        {
            if (mb_strpos($request['delivery_address'], $find->find) !== false) //Если данные для замены найдены
            {
                if (mb_strpos($request['delivery_address'], $find->replace) === false)
                    $request['delivery_address'] = mb_ereg_replace($find->find, $find->replace, $request['delivery_address']);
            }

            if (mb_strpos($request['delivery_address_comment'], $find->find) !== false) //Если данные для замены найдены
            {
                if (mb_strpos($request['delivery_address_comment'], $find->replace) === false)
                    $request['delivery_address_comment'] = mb_ereg_replace($find->find, $find->replace, $request['delivery_address_comment']);
            }

            if (mb_strpos($request['comment'], $find->find) !== false) //Если данные для замены найдены
            {
                if (mb_strpos($request['comment'], $find->replace) === false)
                    $request['comment'] = mb_ereg_replace($find->find, $find->replace, $request['comment']);
            }
        }


        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {
            $currentId = $order->currentState()->id;

            if($request->has('certificates_id')) {
                $order->certificates()->sync($request->get('certificates_id'));
            }

            $this->updateOrderDetails($request, $order);

            $order->update($request->input());

            if($currentId == $order->currentState()->id) {
                $order->states()->save(OrderState::find($request->order_state_id));
            }

        } catch (\Exception $exception) {
            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());

            }else{
                throw $exception;
            }
        }


        DB::commit();

        return redirect()->route('orders.edit', $order->id)->with('success', __('Order updated successfully'));
    }

    /**
     * Обновление Товарных позиций
     *
     * @param OrderRequest $request
     * @param Order $order
     */
    protected function updateOrderDetails(OrderRequest $request, Order $order)
    {
        $orderDetails = collect($request->order_detail);
        $orderDetailsNew = collect($request->order_detail_add);

        //Удаление товарных позиций, присутствующих в заказе и отсутствующих в запросе
        $order->orderDetails()
            ->where('is_exchange', 0)
            ->whereNotIn('id', $orderDetails->keys())
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
            function ($orderDetailData, $orderDetailArray) use ($order) {
                $orderDetail = OrderDetail::create(array_merge($orderDetailData, ['order_id' => $order->id]));
                    $orderDetail->states()
                    ->save(OrderDetailState::find($orderDetailData['order_detail_state_id']));
                    if ($orderDetail->product->category->is_certificate) {
                        Certificate::create([
                            'number' => $orderDetailArray['certificate_number'] ? $orderDetailArray['certificate_number'] : null,
                            'order_detail_id' => $orderDetail->id
                        ]);
                    }
            }
        );

        // Обновление товарных позиций
        $orderDetails->each(
            function ($orderDetailArray, $orderDetailId) use ($order) {

                $orderDetail = OrderDetail::find($orderDetailId);
                $orderDetail->update($orderDetailArray);
                $orderDetail->refresh();

                if ($orderDetail->product->category->is_certificate && $orderDetailArray['certificate_number'] != 0) {
                    if(Certificate::where(['order_detail_id' => $orderDetail->id])->first()) {
                        $orderDetail->certificate()->update([
                            'number' => $orderDetailArray['certificate_number'] ? $orderDetailArray['certificate_number'] : null,
                        ]);
                    } else {
                        Certificate::create([
                            'number' => $orderDetailArray['certificate_number'] ? $orderDetailArray['certificate_number'] : null,
                            'order_detail_id' => $orderDetail->id
                        ]);
                    }
                }

                if (isset($orderDetailArray['order_detail_state_id'])) {
                    $orderDetail->states()->save(OrderDetailState::find($orderDetailArray['order_detail_state_id']));
                }

            }
        );

    }

    /**
     * Получение печатных форм для заказа
     *
     * @param \App\Order $order
     * @return \Illuminate\Http\Response
     */
    public function getPDF(Order $order)
    {
        ThermalPrinter::print($order);
        $template = '<style> .page-break { page-break-after: always; } </style>';
        $replacements = [
            '{Order.number}' => $order->order_number,
            '{Order.date}' => $order->created_at->format('d.m.Y'),
            '{Order.delivery_city}' => $order->delivery_city,
            '{Order.delivery_address}' => $order->getStreetDeliveryAddress(),
            '{Order.date_estimated_delivery}' => is_null(
                $order->date_estimated_delivery
            ) ? '' : Carbon::createFromFormat('d-m-Y', $order->date_estimated_delivery)->format('d.m.Y'),
            '{Order.delivery_start_time}' => $order->delivery_start_time,
            '{Order.delivery_end_time}' => $order->delivery_end_time,
            '{Customer.phone}' => $order->customer->phone,
            '{Customer.name}' => $order->customer->first_name.' '.$order->customer->last_name,
            '{Order.delivery_address_comment}' => $order->delivery_address_comment,
        ];

        if($order->carrier()->exists()) {
            $replacements['{Order.delivery_type}'] = $order->carrier->name;
        }else{
            $replacements['{Order.delivery_type}'] = '';
        }
      
        if(!is_null($order->delivery_start_time) && !is_null($order->delivery_end_time)) {
            $date = strtotime($order->delivery_end_time) - strtotime($order->delivery_start_time);
            $replacements['{Order.delivery_time}'] = gmdate('H:i',$date);
        } else {
            $replacements['{Order.delivery_time}'] = '';
        }

        $firstPage = true;

        /**
         * @var Collection $orderDetails
         */
        foreach ($order->orderDetails->groupBy('printing_group') as $printingGroup => $orderDetails) {
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

            if(strip_tags($allGuarantee) != '') {
                $template .= $allGuarantee;
            }
        }

        //Формирование шаблона курьера
        $currentCourierTemplate = $order->channel->courier_template;
        if(strip_tags($currentCourierTemplate) != '') {
        $sum = 0;
        $replacements['<tr><td>{Order.Invoice.Products}</td></tr>'] = '';
        $replacements['<tr><td>{Order.Cheque.Products}</td></tr>'] = '';

        foreach ($order->orderDetails->groupBy('printing_group') as $orderDetails) {
            $sum += $orderDetails->sum('price');
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
        }
        $replacements['{Order.Invoice.Sum}'] = sprintf("%01.0f", $sum);

        foreach ($replacements as $search => $replace) {
            $currentCourierTemplate = str_replace($search, $replace, $currentCourierTemplate);
        }

        $template .= '<div class="page-break"></div>';
        $template .= $currentCourierTemplate;
        }

        $pdf = App::make('dompdf.wrapper');
        if($order->channel->is_landscape_docs){
            $pdf->setPaper('a4', 'landscape');
        }
        $pdf->loadHTML($template);
        return $pdf->download('orderDocs_'.$order->id.'.pdf');
    }


    public function asyncSelector(Request $request)
    {
        $orders = Order::select('id as name', 'id as value')->orderBy('value')->get();
        $orders = $orders->map(function (Order $order) {
            $order->name = Order::getDisplayNumberById($order->name);
            return $order;
        });
        return response()->json($orders, 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Создание заказа через API
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxStore(Request $request)
    {
        $data = $request->json();
        $validator = Validator::make(
            $data->all(),
            [
                'customerFirstName' => 'required|string|min:1',
                'customerLastName' => 'nullable|string',
                'customerEmail' => 'required_without:customerPhone|email',
                'customerPhone' => 'required_without:customerEmail|regex:/^[\+]{0,1}[0-9\-\(\)\s]+$/',
                'orderState' => 'required|string|exists:order_states,name',
                'channel' => 'required|string|exists:channels,name',
                'deliveryAddressComment' => 'nullable|string',
                'orderComment' => 'nullable|string',
                'clientID' => 'nullable|string',
                'gaClientID' => 'nullable|string',
                'utm_campaign' => 'nullable|string',
                'utm_source' => 'nullable|string',
                'search_query' => 'nullable|string',
                'products' => 'array',
                'products.*.reference' => 'required_with:products|string',
                'products.*.price' => 'required_with:products|numeric|min:0',
                'products.*.currencyName' => 'required_with:products|string|exists:currencies,name',
                'products.*.state' => 'required_with:products|string|exists:order_detail_states,name',
            ]
        );

        if ($validator->fails()) {
            return response()->json($validator->getMessageBag()->getMessages());
        }

        $result = [];
        $phone = $data->get('customerPhone');
        try {
            switch (substr($phone, 0, 1)) {
                case '8':
                    $phone = substr_replace($phone, '7', 0, 1);
                    break;
            }
        } catch (\Exception $e) {
        }
        if ($data->get('token') == 'iuggisofdgosu8e4jcby7e') {
            //Получение данных клиента из запроса
            $customerData = array_filter(
                [
                    'first_name' => $data->get('customerFirstName'),
                    'last_name' => $data->get('customerLastName'),
                    'email' => $data->get('customerEmail'),
                    'phone' => ($phone ? preg_replace(
                        '/[\s\+\(\)\-]/',
                        '',
                        $phone
                    ) : null),
                ],
                function ($item) {
                    return (bool)$item;
                }
            );

            //Поиск или создание клиента
            DB::beginTransaction();
            /**
             * @var Customer $customer
             */
            $customer = Customer::firstOrCreate($customerData);

            //Создание заказа с первичными данными - клиент, адрес, источник
            try {
                $addingOrder = $customer->orders()->where(
                    'channel_id',
                    Channel::where('name', $data->get('channel'))->firstOrFail()->id
                )->where('is_new_api_order', 1)->latest()->first();
                if ((bool)$addingOrder) {
                    $order = $addingOrder;
                    $deliveryAddressComment = ($addingOrder->delivery_address_comment && $addingOrder->delivery_address_comment != $data->get(
                        'deliveryAddressComment'
                    ) ? $addingOrder->delivery_address_comment.'; '.$data->get(
                            'deliveryAddressComment'
                        ) : $data->get(
                        'deliveryAddressComment'
                    ));
                    $orderComment = ($addingOrder->comment && $addingOrder->comment != $data->get(
                        'orderComment'
                    ) ? $addingOrder->comment.'; '.$data->get(
                            'orderComment'
                        ) : $data->get(
                        'orderComment'
                    ));
                    $order->update(
                        [
                            'delivery_address_comment' => $deliveryAddressComment,
                            'comment' => $orderComment,
                            'clientID' => $data->get('clientID') ?: $addingOrder->clientID,
                            'gaClientID' => $data->get('gaClientID') ?: $addingOrder->gaClientID,
                            'search_query' => $data->get('search_query') ?: $addingOrder->search_query,
                        ]
                    );

                    try {
                        $order->utm_id =
                            Utm::firstOrCreate(
                                [
                                    'utm_campaign_id' =>
                                        (UtmCampaign::firstOrCreate(['name' => $data->get('utm_campaign') ?: $addingOrder->utm_campaign]))->id,
                                    'utm_source_id' =>
                                        (UtmSource::firstOrCreate(['name' => $data->get('utm_source') ?: $addingOrder->utm_source]))->id,
                                ]
                            )->id;

                        $order->save();
                    } catch (\Exception $e) {

                    }
                } else {
                    $order = Order::create(
                        [
                            'customer_id' => $customer->id,
                            'delivery_address_comment' => $data->get('deliveryAddressComment'),
                            'channel_id' => Channel::where('name', $data->get('channel'))->firstOrFail()->id,
                            'comment' => $data->get('orderComment'),
                            'is_new_api_order' => 1,
                            'clientID' => $data->get('clientID'),
                            'gaClientID' => $data->get('gaClientID'),
                            'utm_campaign' => $data->get('utm_campaign'),
                            'utm_source' => $data->get('utm_source'),
                            'search_query' => $data->get('search_query'),
                        ]
                    );

                    try {
                        $order->utm_id =
                            Utm::firstOrCreate(
                                [
                                    'utm_campaign_id' =>
                                        (UtmCampaign::firstOrCreate(['name' => $data->get('utm_campaign')]))->id,
                                    'utm_source_id' =>
                                        (UtmSource::firstOrCreate(['name' => $data->get('utm_source')]))->id,
                                ]
                            )->id;

                        $order->save();
                    } catch (\Exception $e) {

                    }
                }
            } catch (ModelNotFoundException $e) {
                switch ($e->getModel()) {
                    case 'Channel':
                        $result['errors'][] = __(
                            'Order has the wrong channel.'
                        );
                        break;
                    default:
                        $result['errors'][] = __(
                            'An error occurred while adding the order.'
                        );
                }

                DB::rollBack();

                return response()->json($result);
            } catch (\Exception $e) {
                $result['errors'][] = __(
                    'An error occurred while adding the order.'
                );

                DB::rollBack();

                return response()->json($result);
            }

            //Если utm no то смотрим не органика ли это
            try {
                if (!$order->utm_campaign && !$order->utm_source) {
                    $headers['Authorization'] = 'OAuth ' . $order->channel->yandex_token;
                    $url = 'https://api-metrika.yandex.ru/stat/v1/data?ids=' . $order->channel->yandex_counter . '&date1=2018-11-06&filters=ym:s:clientID==' . $order->clientID . '&metrics=ym:s:visits&dimensions=ym:s:clientID,ym:s:trafficSource,ym:s:UTMCampaign,ym:s:UTMSource,ym:s:UTMTerm&limit=10000';
                    if (!is_null($order->channel->go_proxy_url)) {
                        $headers['Go'] = $url;
                        $url = $order->channel->go_proxy_url;
                    }

                    $response = Unirest\Request::get($url, $headers);
                    if (!empty($response->body->data)) {
                        $organic = false;
                        //Смотрим список визитов, если имеется реклама (ad), то не учитываем
                        foreach ($response->body->data as $visit) {
                            if ($visit->dimensions[1]->id == 'ad') {
                                $organic = false;
                                break;
                            }

                            if ($visit->dimensions[1]->id == 'organic') {
                                $organic = true;
                            }
                        }
                        if ($organic) {
                            $order->utm()->associate(Utm::firstOrCreate(
                                [
                                    'utm_campaign_id' =>
                                        UtmCampaign::firstOrCreate(['name' => 'organic'])->id,
                                    'utm_source_id' =>
                                        UtmSource::firstOrCreate(['name' => 'organic'])->id,
                                ]
                            ));
                            $order->save();
                        }
                    }
                }
            } catch (\Exception $exception) {

            }

            //Простановка первичного статуса заказу
            try {
                if (!(bool)$addingOrder) {
                    $order->states()->save(OrderState::where('name', $data->get('orderState'))->firstOrFail());
                }
            } catch (ModelNotFoundException $e) {
                switch ($e->getModel()) {
                    case 'OrderState':
                        $result['errors'][] = __(
                            'Order has the wrong state.'
                        );
                        break;
                    default:
                        $result['errors'][] = __(
                            'An error occurred while adding the order.'
                        );
                }

                DB::rollBack();

                return response()->json($result);
            } catch (\Exception $e) {
                $result['errors'][] = __(
                    'An error occurred while adding the order.'
                );

                DB::rollBack();

                return response()->json($result);
            }

            //Получение товаров из запроса
            $products = ($data->get('products') ?: []);

            //Присоединение товаров к заказу
            foreach ($products as $product) {
                try {
                    $orderDetail = [
                        'order_id' => $order->id,
                        'product_id' => Product::where('reference', $product['reference'])->firstOrFail()->id,
                        'price' => $product['price'],
                        'currency_id' => Currency::where('name', $product['currencyName'])->firstOrFail()->id,
                        'store_id' => Store::firstOrFail()->id,
                    ];
                    $orderDetailState = OrderDetailState::where('name', $product['state'])->firstOrFail();
                    OrderDetail::create($orderDetail)->states()->save($orderDetailState);
                } catch (ModelNotFoundException $e) {
                    switch ($e->getModel()) {
                        case 'Product':
                            $result['errors'][] = __(
                                'Product with reference :reference not found.',
                                ['reference' => $product['reference']]
                            );
                            break;
                        case 'Currency':
                            $result['errors'][] = __(
                                'Product with reference :reference has the wrong currency.',
                                ['reference' => $product['reference']]
                            );
                            break;
                        case 'Store':
                            $result['errors'][] = __(
                                'For product with reference :reference no store have been found.',
                                ['reference' => $product['reference']]
                            );
                            break;
                        case 'OrderDetailState':
                            $result['errors'][] = __(
                                'For product with reference :reference the status is incorrect.',
                                ['reference' => $product['reference']]
                            );
                            break;
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = __(
                        'An error occurred while adding the product with reference :reference.',
                        ['reference' => $product['reference']]
                    );
                }
            }

            if (isset($result['errors'])) {
                DB::rollBack();
            } else {
                DB::commit();
                $result['id'] = $order->getDisplayNumber();
            }
        }

        return response()->json($result);
    }
}
