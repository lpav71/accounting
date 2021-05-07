<?php
namespace App\Observers;
use App\CourierTask;
use App\RoutePoint;
use App\RoutePointState;
use Illuminate\Support\Facades\Auth;

class CourierTaskObserver
{
    public function belongsToManyAttaching(string $relation, CourierTask $courierTask, array $ids, array $attributes)
    {
        $result = true;

        switch ($relation) {
            case 'states':
                // Указание текущего пользователя автором статуса
                if (!isset($attributes['user_id'])) {
                    $result = false;
                    $ids = reset($ids);
                    $attributes['user_id'] = Auth::id() ?: 0;
                }

                if (!$result) {
                    $courierTask->states()->attach($ids, $attributes);
                    break;
                }
        }

        return $result;
    }

    /**
     * @param $relation
     * @param CourierTask $courierTask
     * @param $ids
     */
    public function belongsToManyAttached($relation, CourierTask $courierTask, $ids)
    {
        if(count($courierTask->routePoints())) {
            $this->changeRoutePointState($courierTask);
        }
    }

    /**
     * @param CourierTask $productReturn
     */
    protected function changeRoutePointState(CourierTask $courierTask)
    {
        if($courierTask->currentState()->is_successful && !$courierTask->currentState()->is_courier_state) {
            /**
             * @var $routePoint RoutePoint
             */
            foreach ($courierTask->routePoints() as $routePoint) {
                $routePoint->states()->save(RoutePointState::where('is_successful', 1)->first());
            }
        } elseif ($courierTask->currentState()->is_failure && !$courierTask->currentState()->is_courier_state) {
            /**
             * @var $routePoint RoutePoint
             */
            foreach ($courierTask->routePoints() as $routePoint) {
                $routePoint->states()->save(RoutePointState::where('is_failure', 1)->first());
            }
        }
    }
}