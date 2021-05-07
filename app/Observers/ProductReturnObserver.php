<?php

namespace App\Observers;

use App\Cashbox;
use App\Currency;
use App\Exceptions\DoingException;
use App\Operation;
use App\OperationState;
use App\OrderDetail;
use App\ProductReturn;
use App\ProductReturnState;
use App\RoutePoint;
use App\RoutePointState;
use Config;

class ProductReturnObserver
{
    /**
     * Обработка события 'created'
     *
     * @param  ProductReturn $productReturn
     * @return void
     */
    public function created(ProductReturn $productReturn)
    {
        $productReturn->order->comments()->create(
            [
                'comment' => __('Return created')." #productReturn-{$productReturn->id}",
                'user_id' => \Auth::id() ?: null,
            ]
        );
    }

    /**
     * Обработка события 'updated'
     *
     * @param  ProductReturn $productReturn
     */
    public function updated(ProductReturn $productReturn)
    {

        $productReturn
            ->routePoints()
            ->filter(
                function (RoutePoint $routePoint) {
                    return $routePoint->is_point_object_attached;
                }
            )
            ->each(
                function (RoutePoint $routePoint) use ($productReturn) {
                    $routePoint->update(
                        [
                            'delivery_post_index' => $productReturn->delivery_post_index,
                            'delivery_city' => $productReturn->delivery_city,
                            'delivery_address' => $productReturn->delivery_address,
                            'delivery_flat' => $productReturn->delivery_flat,
                            'delivery_comment' => $productReturn->delivery_comment,
                            'delivery_start_time' => $productReturn->delivery_start_time,
                            'delivery_end_time' => $productReturn->delivery_end_time,
                        ]
                    );
                }
            );
    }

    /**
     * Обработка события 'belongsToManyAttaching'
     *
     * @param string $relation
     * @param ProductReturn $productReturn
     * @param array $ids
     * @return bool
     * @throws DoingException
     */
    public function belongsToManyAttaching(string $relation, ProductReturn $productReturn, array $ids)
    {
        $doingErrors = [];

        $result = true;

        switch ($relation) {
            case 'states':

                if (count($ids) > 1) {
                    $doingErrors[] = __('It is prohibited to change several statuses in a single operation.');
                }

                $newState = ProductReturnState::find(reset($ids));

                if ($productReturn->currentState() && $productReturn->currentState()->id == $newState->id) {
                    $result = false;
                    break;
                }

                if (!$productReturn->currentState() && $newState->previousStates->isNotEmpty()) {
                    $doingErrors[] = __(
                        'The status ":state" may not be the first status of product return.',
                        ['state' => $newState->name]
                    );
                    break;
                }

                if ($productReturn->currentState() && !$newState->previousStates->find(
                        $productReturn->currentState()->id
                    )) {
                    $doingErrors[] = __(
                        'Change of product return status from ":oldState" to ":newState" is not possible.',
                        [
                            'oldState' => $productReturn->currentState()->name,
                            'newState' => $newState->name,
                        ]
                    );
                    break;
                }

                $this->checkNeedOrderDetailsStates($productReturn, $newState, $doingErrors);

                if (count($doingErrors)) {
                    break;
                }


                if ($newState->newOrderDetailState) {
                    $productReturn->orderDetails->each(
                        function (OrderDetail $orderDetail) use ($newState) {

                            $orderDetail->states()->save($newState->newOrderDetailState);

                        }
                    );
                }

                $this->checkOrderBalance($productReturn, $newState, $doingErrors);

                break;
        }

        DoingException::processErrors($doingErrors);

        return $result;
    }

    /**
     * Проверка баланса для статуса при необходимости
     *
     * @param ProductReturn $productReturn
     * @param ProductReturnState $productReturnState
     * @param $doingErrors
     */
    protected function checkOrderBalance(
        ProductReturn $productReturn,
        ProductReturnState $productReturnState,
        &$doingErrors
    ) {
        if (!$productReturnState->check_payment) {
            return;
        }


        $productReturnCurrencies = $productReturn
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
        foreach ($productReturnCurrencies as $currency) {

            $balance = $productReturn->order->getOrderBalance($currency);

            if ($balance < 0) {

                $accumulator = Config::get("cashbox.accumulator.currency.{$currency->id}", false);
                /**
                 * @var Cashbox|null $cashbox
                 */
                $cashbox = Config::get("cashbox.accumulator.cashboxes.".ProductReturn::class.".{$productReturn->id}");

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
                            'order_id' => $productReturn->order->id,
                            'product_return_id' => $productReturn->id,
                            'comment' => __('Credit by product return'),
                        ]
                    )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);

                    Config::set("cashbox.accumulator.currency.{$currency->id}", $accumulator - $balance);
                }
            }
        }
    }

    /**
     * @param $relation
     * @param ProductReturn $productReturn
     * @param $ids
     */
    public function belongsToManyAttached($relation, ProductReturn $productReturn, $ids)
    {
        $state = ProductReturnState::find(reset($ids));
        if(count($productReturn->routePoints())) {
            $this->changeRoutePointState($productReturn,$state);
        }
    }

    /**
     * Изменение курьерского статуса на соответствующий ему конечный для работы из маршрутного листа
     *
     * @param ProductReturn $productReturn
     * @param ProductReturnState $productReturnState
     */
    protected function changeCourierStates(ProductReturn $productReturn, ProductReturnState $productReturnState)
    {
        foreach ($productReturn->orderDetails as $orderDetail) {
            if ($orderDetail->currentState()->is_courier_state) {
                if ($orderDetail->currentState()->is_delivered) {
                    $orderDetail->states()->save($orderDetail->nextStates->where('is_courier_state', 0)->where('is_delivered', 1)->first());
                } else {
                    $orderDetail->states()->save($orderDetail->nextStates->where('is_courier_state', 0)->where('is_returned', 1)->first());
                }
            }
        }
    }

    /**
     * Проверка необходимых статусов товарных позиций для смены статуса возврата
     *
     * @param ProductReturn $productReturn
     * @param ProductReturnState $productReturnState
     * @param $doingErrors
     */
    protected function checkNeedOrderDetailsStates(
        ProductReturn $productReturn,
        ProductReturnState $productReturnState,
        &$doingErrors
    ) {
        $needOrderDetailStates = $productReturnState->needOrderDetailStates()->get()->groupBy('id');

        if($productReturnState->is_successful || $productReturnState->is_failure) {
            $this->changeCourierStates($productReturn, $productReturnState);
        }

        if ($needOrderDetailStates->isNotEmpty()) {

            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($productReturn->orderDetails as $orderDetail) {

                if (!$needOrderDetailStates->has($orderDetail->currentState()->id)) {
                    $doingErrors[] = __(
                        'Order details have not need state: :states',
                        [
                            'states' => $productReturnState
                                ->needOrderDetailStates()
                                ->get()
                                ->pluck('name')
                                ->implode(' '.__('or').' '),
                        ]
                    );
                }

            }
        }


        $needOneOrderDetailStates = $productReturnState->needOneOrderDetailStates()->get()->groupBy('id');

        if ($needOneOrderDetailStates->isNotEmpty()) {

            $noNeed = true;

            /**
             * @var OrderDetail $orderDetail
             */
            foreach ($productReturn->orderDetails as $orderDetail) {

                if ($needOneOrderDetailStates->has($orderDetail->currentState()->id)) {

                    $noNeed = false;
                    continue;
                }

            }

            if ($noNeed) {
                $doingErrors[] = __(
                    'Not one Order details have not need state: :states',
                    [
                        'states' => $productReturnState
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
     * @param ProductReturn $productReturn
     */
    protected function changeRoutePointState(ProductReturn $productReturn,ProductReturnState $productReturnState)
    {
        if($productReturnState->is_successful) {
            /**
             * @var $routePoint RoutePoint
             */
            foreach ($productReturn->routePoints() as $routePoint) {
                $routePoint->states()->save(RoutePointState::where('is_successful', 1)->first());
            }
        } elseif ($productReturnState->is_failure) {
            /**
             * @var $routePoint RoutePoint
             */
            foreach ($productReturn->routePoints() as $routePoint) {
                $routePoint->states()->save(RoutePointState::where('is_failure', 1)->first());
            }
        }
    }
}
