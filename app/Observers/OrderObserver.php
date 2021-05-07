<?php

namespace App\Observers;

use App\Carrier;
use App\Cashbox;
use App\Certificate;
use App\Channel;
use App\Currency;
use App\Customer;
use App\Exceptions\DoingException;
use App\Operation;
use App\OperationState;
use App\Order;
use App\OrderDetail;
use App\OrderDetailState;
use App\OrderState;
use App\Role;
use App\RoutePoint;
use App\RoutePointState;
use App\Services\SecurityService\SecurityService;
use App\User;
use Appwilio\CdekSDK\CdekClient;
use Appwilio\CdekSDK\Common\Package;
use Appwilio\CdekSDK\Common\Item;
use Appwilio\CdekSDK\Common\Address;
use Appwilio\CdekSDK\Common\City;
use Appwilio\CdekSDK\Common\AdditionalService;
use Appwilio\CdekSDK\Common\Order as CDEKOrder;
use Appwilio\CdekSDK\Requests\DeliveryRequest;
use Appwilio\CdekSDK\Responses\DeliveryResponse;
use App\Jobs\SendNotificationEmail;
use App\Jobs\SendNotificationSms;

use Auth;
use Config;
use Doctrine\Common\Annotations\AnnotationRegistry;
use http\Message\Body;
use Request;
use App\Services\Messenger\StaticFactory as MessengerFactory;

class OrderObserver
{

    /**
     * Handle the order "created" event.
     *
     * @param \App\Order $order
     * @return void
     */
    public function created(Order $order)
    {
        $order->comments()->create(
            [
                'comment' => __('Order created'),
                'user_id' => \Auth::id() ?: null,
            ]
        );
        //комментарий с заказами пользователя в других магазинах
        $customers = Customer::where('phone', $order->customer->phone)->orWhere(function ($query) use ($order) {
            $query->where('email', $order->customer->email)
                ->where('email','not like','%@onetwotime.ru%')
                ->whereNotNull('email');
        })->get();
        foreach ($customers as $customer) {
            foreach ($customer->orders as $customerOrder) {
                if ($customerOrder->is_hidden) {
                    continue;
                }
                if ($customerOrder->channel_id == $order->channel_id) {
                    continue;
                }
                $order->comment = trim($order->comment . ' ' . __('Was order :number from :channel', [
                    'number' => $customerOrder->getOrderNumber(),
                    'channel' => $customerOrder->channel->name
                ]) . '.');
            }
        }
        $order->save();
    }


    /**
     * Handle the order "updated" event.
     *
     * @param \App\Order $order
     * @return void
     */
    public function updated(Order $order)
    {
        $changes = [];

        $names = [
            'delivery_post_index' => __('Postal index'),
            'delivery_city' => __('Delivery City'),
            'delivery_address' => __('Delivery address'),
            'delivery_address_flat' => __('Delivery flat'),
            'delivery_address_comment' => __('Delivery comment'),
            'delivery_shipping_number' => __('Shipping number'),
            'date_estimated_delivery' => __('Estimated delivery date'),
            'delivery_start_time' => __('Delivery start time'),
            'delivery_end_time' => __('Delivery end time'),
            'comment' => __('Comment'),
            'pickup_point_code' => __('Pickup point Code'),
            'pickup_point_name' => __('Pickup point name'),
            'pickup_point_address' => __('Pickup point address'),
        ];
        $service = new SecurityService();

        if($order->getOriginal('date_estimated_delivery') && in_array('date_estimated_delivery', array_keys($order->getDirty()))) {
            if($order->getOriginal('date_estimated_delivery') != $order->getDirty()['date_estimated_delivery']) {
                foreach ($order->routePoints() as $routePoint) {
                    $routePoint->delete();
                }
                if($order->getDirty()['date_estimated_delivery'] !== null) {
                    $service->checkDateEstimateDelivery($order, $order->getDirty()['date_estimated_delivery']);
                } else {
                    $service->emptyDateEstimated($order);
                }
            }
            $service->checkSumDateEstimateDelivery($order);
        }
        if($order->getOriginal('carrier_id') && in_array('carrier_id', array_keys($order->getDirty())) && $order->getOriginal('carrier_id') != $order->getDirty()['carrier_id']) {
            foreach ($order->routePoints() as $routePoint) {
                $routePoint->delete();
            }
            $service->checkCarrier($order);
        }

        if(in_array('is_hidden', array_keys($order->getDirty()))) {
            $service->checkHiddenOrder($order);
        }

        foreach ($order->getDirty() as $name => $value) {


            if (!in_array($name, array_keys($names))) {
                switch ($name) {
                    case 'customer_id':
                        $customer = Customer::find($order->getOriginal('customer_id'));
                        $changes[] = __(
                                'Customer'
                            ).": '".($customer instanceof Customer ? $customer->full_name : '')."' -> '{$order->customer->full_name}'";
                        break;
                    case 'carrier_id':
                        $carrier = Carrier::find($order->getOriginal('carrier_id'));
                        $changes[] = __(
                                'Carrier'
                            ).": '".(($carrier instanceof Carrier && $carrier->exists()) ? $carrier->name : '')."' -> '{$order->carrier->name}'";
                        break;
                    case 'channel_id':
                        $channel = Channel::find($order->getOriginal('channel_id'));
                        $changes[] = __(
                                'Channel'
                            ).": '".($channel instanceof Channel ? $channel->name : '')."' -> '{$order->channel->name}'";
                        break;
                    case 'is_hidden':
                        $changes[] = ((bool)$value ? __('Order hidden') : __('Order displayed'));
                        break;
                    case 'is_new_api_order':
                        if (!(bool)$value) {
                            $changes[] = __('Started editing the order');
                        }
                        break;
                }
            } else {

                $changes[] = "{$names[$name]}: '{$order->getOriginal($name)}' -> '{$value}'";
            }

        }

        if ($changes) {
            $order->comments()->create(
                [
                    'comment' => implode('<br>', $changes),
                    'user_id' => \Auth::id() ?: null,
                ]
            );
        }

        //TODO Возможно это стоит перенести в какой-то общий наблюдатель
        $order
            ->routePoints()
            ->filter(
                function (RoutePoint $routePoint) {
                    return $routePoint->is_point_object_attached;
                }
            )
            ->each(
                function (RoutePoint $routePoint) use ($order) {
                    $routePoint->update(
                        [
                            'delivery_post_index' => $order->delivery_post_index,
                            'delivery_city' => $order->delivery_city,
                            'delivery_address' => $order->delivery_address,
                            'delivery_flat' => $order->delivery_address_flat,
                            'delivery_comment' => $order->delivery_address_comment,
                            'delivery_start_time' => $order->delivery_start_time,
                            'delivery_end_time' => $order->delivery_end_time,
                        ]
                    );
                }
            );
        $order->putChanges();
    }

    /**
     * Обработка события 'belongsToManyAttaching'
     *
     * @param string $relation
     * @param Order $order
     * @param array $ids
     * @param array $attributes
     * @return bool
     * @throws DoingException
     */
    public function belongsToManyAttaching(string $relation, Order $order, array $ids, array $attributes)
    {
        $doingErrors = [];

        $result = true;


        switch ($relation) {
            case 'states':


                if (count($ids) > 1) {
                    $doingErrors[] = __('It is prohibited to change several statuses in a single operation.');
                }

                // Указание текущего пользователя автором статуса
                if (!isset($attributes['user_id'])) {
                    $result = false;
                    $ids = reset($ids);
                    $attributes['user_id'] = Auth::id() ?: 0;
                }

                if (!$result) {
                    $order->states()->attach($ids, $attributes);
                    break;
                }

                $newState = OrderState::find(reset($ids));

                if($newState->check_certificates_number) {
                    $this->checkCertificates($order, $doingErrors);
                }

                if ($order->currentState() && $order->currentState()->id == $newState->id) {
                    $result = false;
                    break;
                }

                if (!$order->currentState() && $newState->previousStates->isNotEmpty()) {
                    $doingErrors[] = __(
                        'The status ":state" may not be the first status of order.',
                        ['state' => $newState->name]
                    );
                    break;
                }

                if (Auth::user()) {
                    $roleOrderStates = \Auth::user()->getOrderStateIdsByRole();

                    if ($order->currentState() && !in_array($order->currentState()->id, $roleOrderStates)) {
                        $doingErrors[] = __('You do not own the current status of the order (:state)', ['state' => $order->currentState()->name]);
                    }

                    if ($order->currentState() && !in_array($newState->id, $roleOrderStates)) {
                        $doingErrors[] = __('You cannot select an order status :state, you do not have permission', ['state' => $newState->name]);
                    }
                }

                if ($order->currentState() && !$newState->previousStates->find($order->currentState()->id)) {
                    $doingErrors[] = __(
                        'Change of order status from ":oldState" to ":newState" is not possible.',
                        [
                            'oldState' => $order->currentState()->name,
                            'newState' => $newState->name,
                        ]
                    );
                    break;
                }

                if (is_object($newState) && is_object($newState->newOrderDetailState) && in_array($newState->newOrderDetailState->store_operation, ['C', 'CR']) && !isset($order->carrier)) {
                    $doingErrors[] = $order->order_number.': '.__('You must select a carrier!');
                    $result = false;
                    break;
                }

                $this->checkNeedOrderDetailsStates($order, $newState, $doingErrors);

                if ($newState->check_carrier) {
                    $deliveryShippingNumber = $order->delivery_shipping_number;
                    $deliveryRoutelist = $order->routeList();
                    if (!isset($order->carrier)) {
                        $doingErrors[] = $order->order_number.': '.__('Need carrier');
                        $result = false;
                        break;
                    } elseif ($order->carrier->is_internal && !isset($deliveryRoutelist)) {
                        $doingErrors[] = $order->order_number.': '.__('Need route list');
                        $result = false;
                        break;
                    } elseif (!$order->carrier->is_internal && !isset($deliveryShippingNumber)) {
                        $doingErrors[] = $order->order_number.': '.__('Need track number');
                        $result = false;
                        break;
                    }

                }

                if (count($doingErrors)) {
                    break;
                }


                if ($newState->newOrderDetailState) {
                    $order->orderDetails->each(
                        function (OrderDetail $orderDetail) use ($newState) {

                            $orderDetail->states()->save($newState->newOrderDetailState);

                        }
                    );
                }

                $this->checkOrderBalance($order, $newState, $doingErrors);

                $service = new SecurityService();
                $service->confirmedAfterFailure($order, $newState);

                break;
        }

        DoingException::processErrors($doingErrors);

        return $result;
    }

    /**
     * @param Order $order
     */
    protected function changeRoutePointState(Order $order)
    {
        if($order->currentState()->is_successful) {
            /**
             * @var $routePoint RoutePoint
             */
            foreach ($order->routePoints() as $routePoint) {
                $routePoint->states()->save(RoutePointState::where('is_successful', 1)->first());
            }
        } elseif ($order->currentState()->is_failure) {
            /**
             * @var $routePoint RoutePoint
             */
            foreach ($order->routePoints() as $routePoint) {
                $routePoint->states()->save(RoutePointState::where('is_failure', 1)->first());
            }
        }
    }

    /**
     * Handle the order "belongsToManyAttached" event.
     *
     * @param string $relation
     * @param Order $order
     * @param array $ids
     * @throws DoingException
     */
    public function belongsToManyAttached($relation, Order $order, $ids)
    {
        $doingErrors = [];

        switch ($relation) {
            case 'states':

                $this->sendExternalDataIfNeed($order, $order->currentState(), $doingErrors);

                $order->comments()->create(
                    [
                        'comment' => __('Order state').': '.$order->currentState()->name,
                        'user_id' => \Auth::id() ?: null,
                    ]
                );

                break;
        }

        if(count($order->routePoints())) {
            $this->changeRoutePointState($order);
        }

        DoingException::processErrors($doingErrors);
    }


    /**
     * Проверка баланса для статуса при необходимости
     *
     * @param Order $order
     * @param OrderState $orderState
     * @param $doingErrors
     */
    protected function checkOrderBalance(Order $order, OrderState $orderState, &$doingErrors)
    {
        if (!$orderState->check_payment) {
            return;
        }


        $orderCurrencies = $order
            ->orderDetails()
            ->select('currency_id')
            ->distinct()
            ->get()
            ->map(
                function ($currency_id) {
                    return Currency::find($currency_id)->first();
                }
            );

        /**
         * @var Currency $currency
         */
        foreach ($orderCurrencies as $currency) {

            $balance = $order->getOrderBalance($currency);

            if ($balance > 0) {

                $accumulator = Config::get("cashbox.accumulator.currency.{$currency->id}", false);

                //Логика работы с сертификатами
                //Получаем баланс всех сертификатов связанных с заказом
                $certificateBalance = $order->getAllCertificateBalance();
                $createDebitOperation = true;

                //Если в аккумуляторе ничего нет, возможно баланса сертификатов хватает что бы оплатить покупку
                if($accumulator === false) {
                    if(($certificateBalance - $balance) >= 0) {
                        $accumulator = $balance;
                        $certificateBalance = $balance;
                        $createDebitOperation = false;
                    }

                    //В другом случае проверяем меньше ли сумма которую внесли требуемой суммы
                    //если да, то проверяем хватает ли средств на сертификате что бы покрыть разницу
                    //если да то вычитаем разницу из сертификата
                } elseif ($accumulator < $balance) {
                    $difference = $balance - $accumulator;
                    if(($certificateBalance - $difference) >= 0) {
                        $certificateBalance = $difference;
                    }

                    //Если суммы хватает что бы оплатить заказ, возможно отсутствие сертификатов либо клиент не захотел
                    //воспользовоться им
                } else {
                    $certificateBalance = 0;
                }

                /**
                 * @var Cashbox|null $cashbox
                 */
                $cashbox = Config::get("cashbox.accumulator.cashboxes.".Order::class.".{$order->id}");

                if ($accumulator === false) {
                    $doingErrors[] = $order->order_number.': '.__(
                            'Need payment on sum :sum for currency :currency',
                            [
                                'sum' => $balance,
                                'currency' => $currency->name,
                            ]
                        );
                } elseif (($accumulator + $certificateBalance) < $balance) {
                    $doingErrors[] = $order->order_number.': '.__(
                            'Need payment on sum :sum for currency :currency',
                            [
                                'sum' => $balance - $accumulator,
                                'currency' => $currency->name,
                            ]
                        );
                } else {
                    $comment = Request::input('comment');
                    if($comment) {
                        $comment =  __('Debit by order') . ', ' . __('Comment to fast close order :comment', ['comment' => $comment]);
                    } else {
                        $comment =  __('Debit by order');
                    }

                    if($createDebitOperation) {
                        Operation::create(
                            [
                                'type' => 'D',
                                'quantity' => $balance,
                                'operable_type' => Currency::class,
                                'operable_id' => $currency->id,
                                'storage_type' => Cashbox::class,
                                'storage_id' => $cashbox->id,
                                'user_id' => \Auth::id(),
                                'order_id' => $order->id,
                                'comment' => $comment,
                            ]
                        )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);

                        Config::set("cashbox.accumulator.currency.{$currency->id}", ($accumulator + $certificateBalance) - $balance);
                    }

                    $this->createCreditCertificateOperations($order, $certificateBalance);
                }
            }
        }
    }

    /**
     * Метод вычитания денежных средств из сертфикикатов присоединенных к заказу
     *
     * @param Order $order
     * @param int $newBalance
     */
    private function createCreditCertificateOperations(Order $order, int $newBalance) : void
    {
        /**
         * @var $certificate Certificate
         */
        foreach ($order->certificates as $certificate) {
            if($newBalance > 0) {
                if($newBalance >= $certificate->getBalance()) {
                    $newBalance -= $certificate->getBalance();
                    $certificate->writingOffMoneyByOrder($certificate->getBalance(), $order);
                } else {
                    $certificate->writingOffMoneyByOrder($newBalance, $order);
                    break;
                }
            } else {
                break;
            }
        }
    }


    /**
     * Проверка необходимых статусов товарных позиций для смены статуса заказа
     *
     * @param Order $order
     * @param OrderState $orderState
     * @param $doingErrors
     */
    protected function checkNeedOrderDetailsStates(Order $order, OrderState $orderState, &$doingErrors)
    {
        $needOrderDetailStates = $orderState->needOrderDetailStates()->get()->groupBy('id');

        if($orderState->is_successful || $orderState->is_failure) {
            $this->changeCourierStates($order);
        }

        if ($needOrderDetailStates->isNotEmpty()) {

            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($order->orderDetails as $orderDetail) {

                if (!$needOrderDetailStates->has($orderDetail->currentState()->id)) {

                    $doingErrors[] = __(
                        'Order details have not need state: :states',
                        [
                            'states' => $orderState
                                ->needOrderDetailStates()
                                ->get()
                                ->pluck('name')
                                ->implode(' '.__('or').' '),
                        ]
                    );
                }

            }
        }


        $needOneOrderDetailStates = $orderState->needOneOrderDetailStates()->get()->groupBy('id');

        if ($needOneOrderDetailStates->isNotEmpty()) {

            $noNeed = true;

            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($order->orderDetails as $orderDetail) {

                if ($needOneOrderDetailStates->has($orderDetail->currentState()->id)) {

                    $noNeed = false;
                    continue;
                }

            }

            if ($noNeed) {
                $doingErrors[] = __(
                    'Not one Order details have not need state: :states',
                    [
                        'states' => $orderState
                            ->needOneOrderDetailStates()
                            ->get()
                            ->pluck('name')
                            ->implode(' '.__('or').' '),
                    ]
                );
            }
        }

    }

    /**
     * Изменение курьерского статуса на соответствующий ему конечный для работы из маршрутного листа
     *
     * @param Order $order
     */
    protected function changeCourierStates(Order $order)
    {
        foreach ($order->orderDetails as $orderDetail) {
            if($orderDetail->currentState()->is_courier_state) {
                if($orderDetail->currentState()->is_delivered) {
                    $orderDetail->states()->save($orderDetail->nextStates->where('is_courier_state', 0)->where('is_delivered', 1)->first());
                } else {
                    $orderDetail->states()->save($orderDetail->nextStates->where('is_courier_state', 0)->where('is_returned', 1)->first());
                }
            }
        }
    }

    /**
     * Отправка связанных со статусом заказа внешних данных
     *
     * @param Order $order
     * @param OrderState $orderState
     * @param $doingErrors
     */
    protected function sendExternalDataIfNeed(Order $order, OrderState $orderState, &$doingErrors)
    {

        $carrierConfig = $order->carrier ? $order->carrier->getConfigVars() : false;

        if ($carrierConfig
            && $orderState->is_sending_external_data
            && (int)$carrierConfig->get('send_data_status') == $orderState->id) {

            if ($carrierConfig->get('operator') == 'cdek') {

                AnnotationRegistry::registerLoader('class_exists');

                $cdekClient = new CdekClient(
                    (string)$carrierConfig->get('operator_account'),
                    (string)$carrierConfig->get('operator_secure')
                );

                $packageCDEK = new Package(
                    [
                        'Number' => $order->order_number,
                        'BarCode' => $order->order_number,
                        'Weight' => (int)$carrierConfig->get('parcel_weight'),
                    ]
                );

                $itemNumber = 1;

                foreach ($order->orderDetails as $orderDetail) {

                    $itemCDEK = new Item(
                        [
                            'WareKey' => $orderDetail->product->reference.'/'.$itemNumber,
                            'Cost' => (bool)$orderDetail->product->need_guarantee ? 3000 : 0.01,
                            //TODO надо перенести это в настройки
                            'Payment' => (string)$carrierConfig
                                ->get('parcel_cashodelivery') == 'true' ? $orderDetail->price : 0,
                            'Weight' => 1,
                            'Amount' => 1,
                            'Comment' => $orderDetail->product->name,
                        ]
                    );

                    $packageCDEK->addItem($itemCDEK);
                    $itemNumber++;
                }

                $addressCDEK = new Address([]);

                switch ($carrierConfig->get('type')) {
                    case 'pickup':

                        $addressCDEK = new Address(['PvzCode' => $order->pickup_point_code]);

                        break;
                    case 'carrier':

                        $addressCDEK = new Address(
                            [
                                'Street' => $order->getStreetDeliveryAddress(),
                                'House' => '-',
                                'Flat' => '-',
                            ]
                        );

                        break;
                }

                $sendCityCDEK = new City(
                    [
                        'Code' => null,
                        'PostCode' => '101000',
                        'Name' => 'Москва',
                    ]
                );

                $recipientCityCDEK = new City(
                    [
                        'Code' => null,
                        'PostCode' => $order->delivery_post_index,
                        'Name' => $order->delivery_city,
                    ]
                );

                $servicesCDEK = [];

                foreach ([30, 36, 37] as $serviceCode) {

                    $servicesCDEK[] = new AdditionalService(
                        [
                            'ServiceCode' => $serviceCode,
                        ]
                    );

                }

                $orderCDEK = (new CDEKOrder(
                    [
                        'Number' => $order->order_number,
                        'SendCity' => $sendCityCDEK,
                        'RecCity' => $recipientCityCDEK,
                        'RecipientName' => Customer::getCustomerName($order->customer),
                        'Phone' => $order->customer->phone,
                        'TariffTypeCode' => (int)$carrierConfig->get('tariff'),
                        'SellerName' => $order->channel->name,
                        'Address' => $addressCDEK,
                        'additionalServices' => $servicesCDEK,
                        'Comment' => $order->delivery_address_comment,
                    ]
                ))->addPackage($packageCDEK);

                $requestCDEK = (new DeliveryRequest(
                    [
                        'number' => $order->order_number,
                    ]
                ))->addOrder($orderCDEK);

                try {

                    $response = $cdekClient->sendDeliveryRequest($requestCDEK);

                } catch (\Throwable $error) {

                    $doingErrors[] = $error->getMessage();

                }

                if (isset($response) && $response instanceof DeliveryResponse) {

                    if (count($response->getOrders())) {

                        $order->update(
                            ['delivery_shipping_number' => $response->getOrders()[0]->getDispatchNumber()]
                        );

                        // отправка СМС сообщения

                        $messenger = MessengerFactory::build('sms');
                        $is_send = $messenger->setDestination($order->customer->phone)->sendOrderTrackUpdated($order);
                        if($is_send){
                            $order->comments()->create(
                                [
                                    'comment' => 'sms ' .__('sent').': '.$messenger->getMessage(),
                                    'user_id' => \Auth::id() ?: null,
                                ]
                            );
                        }

                        //Конец отправки СМС

                    } else {
                        /**
                         * @var \Appwilio\CdekSDK\Responses\Types\Message $message
                         */
                        foreach ($response->getMessages() as $message) {

                            $doingErrors[] = $message->getText();

                        }
                    }

                }

            }

        }

        $channel = $order->channel;
        $state = $orderState->id;
        if ($channel->smtp_is_enabled) {
            $emailTemplate = null;

            if (isset($order->carrier)) {
                $emailTemplate = $channel->notificationTemplates->where('order_state_id', $state)->where(
                    'is_email',
                    1
                )->where('carrier_type_id', $order->carrier->carrier_type_id)->first();
            }
            if (!$emailTemplate) {
                $emailTemplate = $channel->notificationTemplates->where('order_state_id', $state)->where(
                    'is_email',
                    1
                )->where('carrier_type_id', 0)->first();
            }
            if ($emailTemplate) {
                $finalHtml = $emailTemplate->template;
                $emailReplacements = [
                    '{Channel.name}' => $channel->name,
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
                    '{Order.Invoice.Sum}' => $order->orderDetails->sum('price'),
                    '{Channel.phone}' => $order->channel->phone,
                ];

                if (isset($order->carrier) && isset($order->carrier->carrier_type)) {
                    $emailReplacements['{Order.delivery.type}'] = $order->carrier->carrier_type->name;
                }
                if (isset($order->carrier) && $order->carrier->is_internal) {
                    $emailReplacements['{Order.carrier.name}'] = $order->routeList()->courier->name;
                    $emailReplacements['{Order.carrier.phone}'] = $order->routeList()->courier->phone;
                }
                if (isset($order->carrier) && !$order->carrier->is_internal) {
                    $emailReplacements['{Order.delivery.shipping_number}'] = $order->delivery_shipping_number;
                    $emailReplacements['{Order.delivery.checkUrl}'] = $order->carrier->url_link;
                }

                $emailReplacements['<tr><td>{Order.Invoice.Products}</td></tr>'] = '';
                $emailReplacements['<tr><td>{Order.Cheque.Products}</td></tr>'] = '';

                /**
                 * @var OrderDetail $orderDetail
                 */
                $rowNumber = 1;
                foreach ($order->orderDetails as $orderDetail) {
                    $emailReplacements['<tr><td>{Order.Invoice.Products}</td></tr>'] .= '<tr><td>'.$orderDetail->product->name.'</td><td>'.sprintf(
                            "%01.0f",
                            $orderDetail->price
                        ).'</td><td>1</td><td>'.sprintf("%01.0f", $orderDetail->price).'</td></tr>';
                    $emailReplacements['<tr><td>{Order.Cheque.Products}</td></tr>'] .= '<tr><td>'.$rowNumber.'</td><td>'.$orderDetail->product->name.'</td><td>шт</td><td>1</td><td>'.sprintf(
                            "%01.0f",
                            $orderDetail->price
                        ).'</td><td>'.sprintf("%01.0f", $orderDetail->price).'</td></tr>';
                    $rowNumber++;
                }

                foreach ($emailReplacements as $search => $replace) {
                    $finalHtml = str_replace($search, $replace, $finalHtml);
                }

                dispatch(new SendNotificationEmail($order, $finalHtml, $emailTemplate));

            }
        }
        if ($channel->sms_is_enabled) {
            $smsTemplate = null;

            if (isset($order->carrier)) {
                $smsTemplate = $channel->notificationTemplates->where('order_state_id', $state)->where(
                    'is_sms',
                    1
                )->where('carrier_type_id', $order->carrier->carrier_type_id)->first();
            }
            if (!$smsTemplate) {
                $smsTemplate = $channel->notificationTemplates->where('order_state_id', $state)->where(
                    'is_sms',
                    1
                )->where('carrier_type_id', 0)->first();
            }
            if ($smsTemplate) {
                $smsTextReplacements = [
                    '{Channel.name}' => $channel->name,
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
                    '{Order.Invoice.Sum}' => $order->orderDetails->sum('price'),
                    '{Channel.phone}' => $order->channel->phone,
                ];
                if (isset($order->carrier) && isset($order->carrier->carrier_type)) {
                    $smsTextReplacements['{Order.delivery.type}'] = $order->carrier->carrier_type->name;
                }
                if (isset($order->carrier) && $order->carrier->is_internal) {
                    $smsTextReplacements['{Order.carrier.name}'] = $order->routeList()->courier->name;
                    $smsTextReplacements['{Order.carrier.phone}'] = $order->routeList()->courier->phone;
                }
                if (isset($order->carrier) && !$order->carrier->is_internal) {
                    $smsTextReplacements['{Order.delivery.shipping_number}'] = $order->delivery_shipping_number;
                    $smsTextReplacements['{Order.delivery.checkUrl}'] = $order->carrier->url_link;
                }

                $smsTextReplacements['{Order.Invoice.Products}'] = '';

                /**
                 * @var OrderDetail $orderDetail
                 */

                $rowNumber = 1;
                foreach ($order->orderDetails as $orderDetail) {
                    $smsTextReplacements['{Order.Invoice.Products}'] .= $orderDetail->product->name.sprintf(
                            " %01.0f ",
                            $orderDetail->price
                        ).__("rub.").' ';
                }


                $smsText = $smsTemplate->template;
                foreach ($smsTextReplacements as $search => $replace) {
                    $smsText = str_replace($search, $replace, $smsText);
                }
                $smsQueryReplacements = [
                    '{Phones}' => rawurlencode($order->customer->phone),
                    '{Text}' => rawurlencode($smsText),
                ];
                $smsQuery = $channel->sms_template;

                foreach ($smsQueryReplacements as $search => $replace) {
                    $smsQuery = str_replace($search, $replace, $smsQuery);
                }
                dispatch(new SendNotificationSms($smsQuery));
            }

        }

    }

    /**
     * @param Order $order
     * @throws DoingException
     */
    public function updating(Order $order){
        $doingErrors = [];
        if((bool)$order->is_hidden){
            foreach($order->orderDetails as $detail){
                if($detail->currentState()->is_reserved || $detail->currentState()->is_sent || $detail->currentState()->is_shipped){
                    $doingErrors[] = __('Can not hide order product :detail in :orderstate state',[
                            'detail' => $detail->product->name,
                            'orderstate' => $detail->currentState()->name
                    ]);
                }
            }
        }
        DoingException::processErrors($doingErrors);

    }

    /**
     *
     * Запрет перевода в статус собран если нет номера у сертификата
     *
     * @param Order $order
     */
    public function checkCertificates(Order $order, &$doingErrors)
    {
        foreach ($order->orderDetails as $orderDetail) {
            if ($orderDetail->product->category->is_certificate) {
                $certificate = $orderDetail->certificate;
                if (!$certificate->number) {
                    $doingErrors[] = __("When you switch to the status 'collected', all certificates in the order must have numbers. Certificate without number - :certificate_id",
                        [
                            'certificate_id' => $certificate->id,
                        ]);
                }
            }
        }
    }
}
