<?php

namespace App\Observers;

use App\Cashbox;
use App\Currency;
use App\Exceptions\DoingException;
use App\Operation;
use App\OperationState;
use App\OrderDetail;
use App\ProductExchange;
use App\ProductExchangeState;
use App\RoutePoint;
use App\RoutePointState;
use Config;
use http\Exception\BadQueryStringException;
use Illuminate\Support\Facades\Auth;

class ProductExchangeObserver
{
    /**
     * Обработка события 'created'
     *
     * @param  ProductExchange $productExchange
     * @return void
     */
    public function created(ProductExchange $productExchange)
    {
        $productExchange->order->comments()->create(
            [
                'comment' => __('Exchange created')." #productExchange-{$productExchange->id}",
                'user_id' => \Auth::id() ?: null,
            ]
        );
    }

    /**
     * Обработка события 'updated'
     *
     * @param ProductExchange $productExchange
     */
    public function updated(ProductExchange $productExchange)
    {
        $productExchange
            ->routePoints()
            ->filter(
                function (RoutePoint $routePoint) {
                    return $routePoint->is_point_object_attached;
                }
            )
            ->each(
                function (RoutePoint $routePoint) use ($productExchange) {
                    $routePoint->update(
                        [
                            'delivery_post_index' => $productExchange->delivery_post_index,
                            'delivery_city' => $productExchange->delivery_city,
                            'delivery_address' => $productExchange->delivery_address,
                            'delivery_flat' => $productExchange->delivery_flat,
                            'delivery_comment' => $productExchange->delivery_comment,
                            'delivery_start_time' => $productExchange->delivery_start_time,
                            'delivery_end_time' => $productExchange->delivery_end_time,
                        ]
                    );
                }
            );
    }

    /**
     * Обработка события 'belongsToManyAttaching'
     *
     * @param string $relation
     * @param ProductExchange $productExchange
     * @param array $ids
     * @param array $attributes
     * @return bool
     * @throws DoingException
     */
    public function belongsToManyAttaching(string $relation, ProductExchange $productExchange, array $ids, array $attributes)
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
                    $productExchange->states()->attach($ids, $attributes);
                    break;
                }

                $newState = ProductExchangeState::find(reset($ids));

                if ($productExchange->currentState() && $productExchange->currentState()->id == $newState->id) {
                    $result = false;
                    break;
                }

                if (!$productExchange->currentState() && $newState->previousStates->isNotEmpty()) {
                    $doingErrors[] = __(
                        'The status ":state" may not be the first status of product exchange.',
                        ['state' => $newState->name]
                    );
                    break;
                }

                if ($productExchange->currentState() && !$newState->previousStates->find(
                        $productExchange->currentState()->id
                    )) {
                    $doingErrors[] = __(
                        'Change of product exchange status from ":oldState" to ":newState" is not possible.',
                        [
                            'oldState' => $productExchange->currentState()->name,
                            'newState' => $newState->name,
                        ]
                    );
                    break;
                }
                $this->checkNeedOrderDetailsStates($productExchange, $newState, $doingErrors);

                if (count($doingErrors)) {
                    break;
                }


                if ($newState->newOrderDetailState) {
                    $productExchange->orderDetails->each(
                        function (OrderDetail $orderDetail) use ($newState) {

                            $orderDetail->states()->save($newState->newOrderDetailState);

                        }
                    );
                }

                if ($newState->newExchangeOrderDetailState) {
                    $productExchange->exchangeOrderDetails->each(
                        function (OrderDetail $orderDetail) use ($newState) {

                            $orderDetail->states()->save($newState->newExchangeOrderDetailState);

                        }
                    );
                }

                $this->checkOrderBalance($productExchange, $newState, $doingErrors);

                break;
        }

        DoingException::processErrors($doingErrors);

        return $result;
    }

    /**
     * @param $relation
     * @param ProductExchange $productExchange
     * @param $ids
     */
    public function belongsToManyAttached($relation, ProductExchange $productExchange, $ids)
    {
        $state = ProductExchangeState::find(reset($ids));
        if(count($productExchange->routePoints())) {
            $this->changeRoutePointState($productExchange,$state);
        }
    }

    /**
     * Проверка баланса для статуса при необходимости
     *
     * @param ProductExchange $productExchange
     * @param ProductExchangeState $productExchangeState
     * @param $doingErrors
     */
    protected function checkOrderBalance(
        ProductExchange $productExchange,
        ProductExchangeState $productExchangeState,
        &$doingErrors
    ) {
        if (!$productExchangeState->check_payment) {
            return;
        }


        $productExchangeCurrencies = $productExchange
            ->hasMany(OrderDetail::class)
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
        foreach ($productExchangeCurrencies as $currency) {

            $balance = $productExchange->order->getOrderBalance($currency);

            if ($balance < 0) {

                $accumulator = Config::get("cashbox.accumulator.currency.{$currency->id}", false);
                /**
                 * @var Cashbox|null $cashbox
                 */
                $cashbox = Config::get(
                    "cashbox.accumulator.cashboxes.".ProductExchange::class.".{$productExchange->id}"
                );

                if ($accumulator === false) {
                    $doingErrors[] = __(
                        'Need return on sum :sum for currency :currency',
                        [
                            'sum' => $balance * -1,
                            'currency' => $currency->name,
                        ]
                    );
                } else {
                    Operation::create(
                        [
                            'type' => 'C',
                            'quantity' => $balance * -1,
                            'operable_type' => Currency::class,
                            'operable_id' => $currency->id,
                            'storage_type' => Cashbox::class,
                            'storage_id' => $cashbox->id,
                            'user_id' => \Auth::id(),
                            'order_id' => $productExchange->order->id,
                            'product_exchange_id' => $productExchange->id,
                            'comment' => __('Credit by product exchange'),
                        ]
                    )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);

                    Config::set("cashbox.accumulator.currency.{$currency->id}", $accumulator - $balance);
                }
            }

            if ($balance > 0) {

                $accumulator = Config::get("cashbox.accumulator.currency.{$currency->id}", false);
                /**
                 * @var Cashbox|null $cashbox
                 */
                $cashbox = Config::get(
                    "cashbox.accumulator.cashboxes.".ProductExchange::class.".{$productExchange->id}"
                );

                if ($accumulator === false) {
                    $doingErrors[] = __(
                        'Need admission on sum :sum for currency :currency',
                        [
                            'sum' => $balance,
                            'currency' => $currency->name,
                        ]
                    );
                } elseif ($accumulator < $balance) {
                    $doingErrors[] = __(
                        'Need admission on sum :sum for currency :currency',
                        [
                            'sum' => $balance - $accumulator,
                            'currency' => $currency->name,
                        ]
                    );
                } else {
                    Operation::create(
                        [
                            'type' => 'D',
                            'quantity' => $balance,
                            'operable_type' => Currency::class,
                            'operable_id' => $currency->id,
                            'storage_type' => Cashbox::class,
                            'storage_id' => $cashbox->id,
                            'user_id' => \Auth::id(),
                            'order_id' => $productExchange->id,
                            'product_exchange_id' => $productExchange->id,
                            'comment' => __('Admission by product exchange'),
                        ]
                    )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);

                    Config::set("cashbox.accumulator.currency.{$currency->id}", $accumulator - $balance);
                }
            }

        }

    }

    /**
     * Изменение курьерского статуса на соответствующий ему конечный для работы из маршрутного листа
     *
     * @param ProductExchange $productExchange
     */
    protected function changeCourierStates(ProductExchange $productExchange, ProductExchangeState $productExchangeState)
    {
        foreach ($productExchange->orderDetails as $orderDetail) {
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
     * Проверка необходимых статусов товарных позиций для смены статуса обмена
     *
     * @param ProductExchange $productExchange
     * @param ProductExchangeState $productExchangeState
     * @param $doingErrors
     */
    protected function checkNeedOrderDetailsStates(
        ProductExchange $productExchange,
        ProductExchangeState $productExchangeState,
        &$doingErrors
    ) {
        $needOrderDetailStates = $productExchangeState->needOrderDetailStates()->get()->groupBy('id');

        if($productExchangeState->is_successful || $productExchangeState->is_failure) {
            $this->changeCourierStates($productExchange, $productExchangeState);
        }

        if ($needOrderDetailStates->isNotEmpty()) {

            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($productExchange->orderDetails as $orderDetail) {

                if (!$needOrderDetailStates->has($orderDetail->currentState()->id)) {
                    $doingErrors[] = __(
                        'Order details have not need state: :states',
                        [
                            'states' => $productExchangeState
                                ->needOrderDetailStates()
                                ->get()
                                ->pluck('name')
                                ->implode(' '.__('or').' '),
                        ]
                    );
                }

            }
        }


        $needOneOrderDetailStates = $productExchangeState->needOneOrderDetailStates()->get()->groupBy('id');

        if ($needOneOrderDetailStates->isNotEmpty()) {

            $noNeed = true;

            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($productExchange->orderDetails as $orderDetail) {

                if ($needOneOrderDetailStates->has($orderDetail->currentState()->id)) {

                    $noNeed = false;
                    continue;
                }

            }

            if ($noNeed) {
                $doingErrors[] = __(
                    'Not one Order details have not need state: :states',
                    [
                        'states' => $productExchangeState
                            ->needOneOrderDetailStates()
                            ->get()
                            ->pluck('name')
                            ->implode(' '.__('or').' '),
                    ]
                );
            }
        }

        $needExchangeOrderDetailStates = $productExchangeState->needExchangeOrderDetailStates()->get()->groupBy('id');

        if ($needExchangeOrderDetailStates->isNotEmpty()) {

            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($productExchange->exchangeOrderDetails as $orderDetail) {

                if (!$needExchangeOrderDetailStates->has($orderDetail->currentState()->id)) {
                    $doingErrors[] = __(
                        'Exchange Order details have not need state: :states',
                        [
                            'states' => $productExchangeState
                                ->needExchangeOrderDetailStates()
                                ->get()
                                ->pluck('name')
                                ->implode(' '.__('or').' '),
                        ]
                    );
                }

            }
        }


        $needOneExchangeOrderDetailStates = $productExchangeState->needOneExchangeOrderDetailStates()->get()->groupBy(
            'id'
        );

        if ($needOneExchangeOrderDetailStates->isNotEmpty()) {

            $noNeed = true;

            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($productExchange->exchangeOrderDetails as $orderDetail) {

                if ($needOneExchangeOrderDetailStates->has($orderDetail->currentState()->id)) {

                    $noNeed = false;
                    continue;
                }

            }

            if ($noNeed) {
                $doingErrors[] = __(
                    'Not one Exchange Order details have not need state: :states',
                    [
                        'states' => $productExchangeState
                            ->needOneExchangeOrderDetailStates()
                            ->get()
                            ->pluck('name')
                            ->implode(' '.__('or').' '),
                    ]
                );
            }
        }

    }

    /**
     * @param ProductExchange $productExchange
     */
    protected function changeRoutePointState(ProductExchange $productExchange, ProductExchangeState $productExchangeState)
    {
        if($productExchangeState->is_successful) {
            /**
             * @var $routePoint RoutePoint
             */
            foreach ($productExchange->routePoints() as $routePoint) {
                $routePoint->states()->save(RoutePointState::where('is_successful', 1)->first());
            }
        } elseif ($productExchangeState->is_failure) {
            /**
             * @var $routePoint RoutePoint
             */
            foreach ($productExchange->routePoints() as $routePoint) {
                $routePoint->states()->save(RoutePointState::where('is_failure', 1)->first());
            }
        }
    }
}
