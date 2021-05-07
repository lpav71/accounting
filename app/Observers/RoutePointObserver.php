<?php

namespace App\Observers;

use App\CourierTask;
use App\Exceptions\DoingException;
use App\Order;
use App\OrderState;
use App\ProductExchange;
use App\ProductExchangeState;
use App\ProductReturn;
use App\ProductReturnState;
use App\RouteList;
use App\RouteListState;
use App\RoutePoint;
use App\RoutePointState;
use Illuminate\Support\Carbon;

class RoutePointObserver
{
    protected $objectTypes = [];

    public function __construct()
    {
        $this->objectTypes = RoutePoint::POINT_OBJECT_TYPES;
    }


    /**
     * Обработка события 'creating'
     *
     * @param RoutePoint $routePoint
     * @throws DoingException
     * @return boolean
     */
    public function creating(RoutePoint $routePoint)
    {
        $doingErrors = [];
        $result = true;

        if (!in_array($routePoint->point_object_type, $this->objectTypes)) {
            $doingErrors[] = __(
                'Invalid object type of route point. The possible types is: :types.',
                ['types' => implode(', ', $this->objectTypes)]
            );

            $result = false;
        }

        $this->fillAddress($routePoint);

        DoingException::processErrors($doingErrors);

        return $result;
    }

    /**
     * Обработка события 'created'
     *
     * @param RoutePoint $routePoint
     */
    public function created(RoutePoint $routePoint)
    {
        $firstState = RoutePointState::all()
            ->filter(
                function (RoutePointState $routePointState) {
                    return $routePointState->previousStates->isEmpty();
                }
            )
            ->first();

        if (!is_null($firstState)) {
            $routePoint->states()->save($firstState);
        }
    }

    /**
     * Обработка события 'updating'
     *
     * @param RoutePoint $routePoint
     * @return bool
     * @throws DoingException
     */
    public function updating(RoutePoint $routePoint)
    {
        $doingErrors = [];
        $result = true;

        foreach ($routePoint->getAttributes() as $key => $value) {

            if (in_array($key, $routePoint->getCreatableOnly())
                && $value !== $routePoint->getOriginal($key)) {

                $doingErrors[] = __(
                    'The following property of the route point ":point" cannot be edited: :attribute.',
                    [
                        'point' => $routePoint->id,
                        'attribute' => $key,
                    ]
                );

                $result = false;
            }

        }

        DoingException::processErrors($doingErrors);

        return $result;

    }

    /**
     * Обработка события 'deleting'
     *
     * @param RoutePoint $routePoint
     * @throws DoingException
     * @return bool
     */
    public function deleting(RoutePoint $routePoint)
    {
        $doingErrors = [];
        $result = true;

        DoingException::processErrors($doingErrors);

        return $result;
    }

    /**
     * Обработка события 'belongsToManyAttaching'
     *
     * @param string $relation
     * @param RoutePoint $routePoint
     * @param array $ids
     * @return bool
     * @throws DoingException
     */
    public function belongsToManyAttaching(string $relation, RoutePoint $routePoint, array $ids)
    {
        $doingErrors = [];

        $result = true;

        switch ($relation) {
            case 'states':

                if (count($ids) > 1) {
                    $doingErrors[] = __('It is prohibited to change several statuses in a single operation.');
                }

                $newState = RoutePointState::find(reset($ids));

                if ($routePoint->currentState() && $routePoint->currentState()->id == $newState->id) {
                    $result = false;
                    break;
                }

                if (!$routePoint->currentState() && $newState->previousStates->isNotEmpty()) {
                    $doingErrors[] = __(
                        'The status ":state" may not be the first status of route point.',
                        ['state' => $newState->name]
                    );
                    break;
                }

                if ($routePoint->currentState() && !$newState->previousStates->find($routePoint->currentState()->id)) {
                    $doingErrors[] = __(
                        'Change of route point status from ":oldState" to ":newState" is not possible.',
                        [
                            'oldState' => $routePoint->currentState()->name,
                            'newState' => $newState->name,
                        ]
                    );
                    break;
                }

                break;
        }

        DoingException::processErrors($doingErrors);

        return $result;
    }

    /**
     * Присоединение/Отсоединение объекта точки при необходимости
     *
     * @param RoutePoint $routePoint
     * @param RoutePointState $newState
     */
    protected function attachDetachPointObjectByState(RoutePoint $routePoint, RoutePointState $newState)
    {
        if ($newState->is_detach_point_object) {
            $oldRouteList = $routePoint->routeList;
            $routePoint->update(['is_point_object_attached' => 0]);
            if ($newState->is_attach_detached_point_object) {

                $attached = false;
            }
        }
    }

    /**
     * Заполнение адреса маршрутной точки
     *
     * @param RoutePoint $routePoint
     */
    protected function fillAddress(RoutePoint $routePoint)
    {
        $pointObject = $routePoint->pointObject;

        switch ($routePoint->point_object_type) {
            case Order::class:

                $routePoint->fill(
                    [
                        'delivery_post_index' => $pointObject->delivery_post_index,
                        'delivery_city' => $pointObject->delivery_city,
                        'delivery_address' => $pointObject->delivery_address,
                        'delivery_flat' => $pointObject->delivery_address_flat,
                        'delivery_comment' => $pointObject->delivery_address_comment,
                    ]
                );

                break;
            case ProductReturn::class:
            case ProductExchange::class:

                $routePoint->fill(
                    [
                        'delivery_post_index' => $pointObject->delivery_post_index,
                        'delivery_city' => $pointObject->delivery_city,
                        'delivery_address' => $pointObject->delivery_address,
                        'delivery_flat' => $pointObject->delivery_flat,
                        'delivery_comment' => $pointObject->delivery_comment,
                    ]
                );

                break;
            case CourierTask::class:

                $routePoint->fill(
                    [
                        'delivery_address' => $pointObject->address,
                        'delivery_city' => $pointObject->city,
                    ]
                );
        }
    }
}
