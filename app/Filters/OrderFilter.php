<?php

namespace App\Filters;

use App\Channel;
use App\Order;
use App\OrderComment;
use App\Customer;
use App\RuleOrderPermission;
use App\User;
use Illuminate\Support\Facades\Auth;
use Kyslik\LaravelFilterable\Generic\Filter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\OrderState;
use App\Http\Resources\OrderResources;

/**
 * Фильтр заказов
 *
 * @author  Vladimir Tikunov <vtikunov@yandex.ru>
 */
class OrderFilter extends Filter
{
    protected $filterables = ['id', 'delivery_city'];

    public function filterMap(): array
    {
        return [
            'id' => ['id'],
            'orderNumber' => ['orderNumber'],
            'state' => ['state'],
            'employee' => ['employee'],
            'channel' => ['channel'],
            'customer' => ['customer'],
            'carrier' => ['carrier'],
            'date' => ['date'],
            'dateDelivery' => ['dateDelivery'],
            'phone' => ['phone'],
            'address' => ['address'],
            'comment' => ['comment'],
            'redClients' => ['redClients'],
            'prohibition' => ['prohibition']
        ];
    }

    public function prohibition()
    {
        $currentUser = auth()->user();
        $currentRoles = $currentUser->roles;
        $channelForFilter = array();
        $rules = RuleOrderPermission::all();
        foreach ($rules as $rule) {
            $users = $rule->user;
            foreach ($users as $user)
            {
                if ($currentUser->id == $user->id)
                {
                    //Формирование массива запрещённых магазинов ----------------------------------------------
                    $channels = $rule->channel->pluck('id', 'id');
                    $channels = $channels->toArray();
                    if (count($channelForFilter) == 0) {
                        $channelForFilter = $channels;
                    } else $channelForFilter = array_replace($channelForFilter, $channels);
                    //-----------------------------------------------------------------------------------------
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
                        $channels = $rule->channel->pluck('id', 'id');
                        $channels = $channels->toArray();
                        if (count($channelForFilter) == 0) {
                            $channelForFilter = $channels;
                        } else $channelForFilter = array_replace($channelForFilter, $channels);
                        //-----------------------------------------------------------------------------------------
                    }
                }
            }
        }
        return $this->builder->whereNotIn('channel_id', $channelForFilter);
    }

    /**
     * Фильтр по Id заказа
     *
     * @param int $orderId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function id($orderId = 0)
    {
        $orderId = is_numeric($orderId) ? $orderId : 0;

        return (13550 < $orderId ? $this->builder->where('id', $orderId - 13550) : $this->builder); //TODO Надо убрать поправку в модель Order
    }

    /**
     * Фильтр по номеру заказа
     *
     * @param int $orderNumber
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function orderNumber(int $orderNumber = 0)
    {
        return ($orderNumber ? $this->builder->where('order_number', $orderNumber) : $this->builder);
    }

    /**
     * Фильтр по статусу
     *
     * @param int $stateId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function state($stateIds = 0)
    {
        if ($stateIds) {
            //time of last order state's creating
            $latestStates = \DB::table('order_order_state')
                ->select('order_id', \DB::raw('MAX(created_at) as last_state_created_at'))
                ->groupBy('order_id');

            //orders with need states
            $orderIds = \DB::table('order_order_state')
                ->joinSub($latestStates, 'latest_states', function ($join) {
                    $join->on('order_order_state.order_id', '=', 'latest_states.order_id')
                        ->on('order_order_state.created_at', '=', 'latest_states.last_state_created_at');
                })
                ->whereIn('order_order_state.order_state_id', $stateIds)
                ->pluck('order_order_state.order_id');
            return $this->builder->whereIn('id', $orderIds);
        } else {
            return $this->builder;
        }
    }

    /**
     * Фильтр по исполнителю
     *
     * @param int $userId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function employee($userId = 0)
    {
        return ($userId ? $this->builder->whereIn('id', Order::where('user_id', $userId)->pluck('id')) : $this->builder);
    }

    /**
     * Фильтр по источнику
     *
     * @param int $channelId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function channel($channelId = 0)
    {
        return ($channelId ? $this->builder->whereIn('id', Order::where('channel_id', $channelId)->pluck('id')) : $this->builder);
    }

    /**
     * Фильтр по клиенту
     *
     * @param int $customerId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function customer($customerId = 0)
    {
        return ($customerId ? $this->builder->whereIn('id', Order::where('customer_id', $customerId)->pluck('id')) : $this->builder);
    }

    /**
     * Фильтр по службе доставки
     *
     * @param int $carrierId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function carrier($carrierId = 0)
    {
        return ($carrierId ? $this->builder->whereIn('id', Order::whereIn('carrier_id', $carrierId)->pluck('id')) : $this->builder);
    }

    /**
     * Фильтр по планируемой дате доставки
     *
     * @param int $date
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function dateDelivery($date = 0)
    {
        return ($date ? $this->builder->where('date_estimated_delivery', Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d')) : $this->builder);
    }

    /**
     * Фильтр по дате создания
     *
     * @param int $date
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function date($date = 0)
    {
        return ($date ?
            $this
                ->builder
                ->where('created_at', '>=', Carbon::createFromFormat('d-m-Y', $date)->setTime(0, 0, 0, 0)->toDateTimeString())
                ->where('created_at', '<', Carbon::createFromFormat('d-m-Y', $date)->addDay()->setTime(0, 0, 0, 0)
                    ->toDateTimeString())
            : $this->builder);
    }

    /**
     * Фильтр по номеру телефона
     *
     * @param string $phone
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function phone($phone = null)
    {
        return ($phone ? $this->builder->whereIn('customer_id', Customer::where('phone', 'LIKE', '%' . $phone . '%')->pluck('id')) : $this->builder);
    }

    /**
     * Фильтр по адресу доставки
     *
     * @param string $address
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function address($address = null)
    {
        return ($address ? $this->builder->where('delivery_address', 'LIKE', '%' . $address . '%') : $this->builder);
    }

    /**
     * Фильтр по истории комментария
     *
     * @param string $comment
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function comment($comment = null)
    {
        return ($comment ? $this->builder->whereIn('id', OrderComment::where('comment', 'LIKE', '%' . $comment . '%')->distinct()->pluck('order_id')) : $this->builder);
    }

    public function redClients($redClients = null)
    {
        if ($redClients) {
            $redClientsId = OrderResources::FilterRedOrders();
            if ($redClientsId) {
                return $this->builder->whereIn('id', $redClientsId);
            } else {
                return $this->builder;
            }
        } else {
            return $this->builder;
        }
    }

    protected function settings()
    {
        $this->for(['delivery_city'])->setDefaultFilterType('~');
    }
}
