<?php


namespace App\Services\SecurityService;


use App\Cashbox;
use App\Currency;
use App\Customer;
use App\Jobs\TelegramMessage;
use App\ModelChange;
use App\Operation;
use App\Order;
use App\OrderDetail;
use App\OrderDetailState;
use App\OrderState;
use App\Product;
use App\Store;
use App\Task;
use App\User;
use Carbon\Carbon;
use Log;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tightenco\Parental\Tests\Models\Car;

class SecurityService
{
    /**
     * @var string
     */
    private $botName = 'security_bot';

    /**
     * @var string
     */
    private $message;

    /**
     * Отправка сообщения в телеграм
     */
    protected function sendTelegramMessage()
    {
        Log::channel('security_log')->info($this->message);

        TelegramMessage::dispatch(
            [
                'chat_id' => config('telegram.bots.security_bot.chat'),
                'text' => $this->message.PHP_EOL,
                'parse_mode' => 'Markdown',
            ],
            $this->botName
        )->onQueue('telegram_message');

        $this->message = '';
    }

    /**
     * Проверка условий скрытия заказа
     *
     * @param Order $order
     */
    public function checkHiddenOrder(Order $order)
    {
        $orderDetails = $order->orderDetails()->pluck('id')->toArray();
        $orderDetailAllStates = DB::table('order_detail_order_detail_state')
            ->select('order_detail_state_id')
            ->whereIn('order_detail_id', $orderDetails)
            ->pluck('order_detail_state_id');
        $orderDetailStates = OrderDetailState::whereIn('id', $orderDetailAllStates)->get();
        /**
         * @var $orderDetailState OrderDetailState
         */
        foreach ($orderDetailStates as $orderDetailState) {
            if ($orderDetailState->is_shipped || $orderDetailState->is_reserved) {
                $this->message .= __('Some products has state :state', ['state' => $orderDetailState->name]) . "\n";
            }
        }

        if (!$order->currentState()->is_new && !$order->currentState()->is_failure) {
            $this->message .= __('Order current state :state', ['state' => $order->currentState()->name]) . "\n";
        }

        if (!empty(Operation::where('type', '=', 'D')->where('operable_type', '=', Currency::class)->where('order_id', '=', $order->id)->get()->toArray())) {
            $this->message .= __('Payments made for this order') . "\n";
        }

        if (!empty($this->message)) {
            $this->message = __('Transfer to hidden order, order id - :id, user id - :userId, user name - :userName',
                    [
                        'id' => $order->getDisplayNumber(),
                        'userId' => \Auth::user()->id,
                        'userName' => \Auth::user()->name

                    ]
                ) . "\n" . $this->message;
            $this->sendTelegramMessage();
        }
    }

    /**
     * Изменение даты доставки
     *
     * @param Order $order
     * @param $newDate
     */
    public function checkDateEstimateDelivery(Order $order, $newDate)
    {
        $tmp = explode(' ', $order->getOriginal('date_estimated_delivery'));
        $tmp = explode('-', $tmp[0]);
        $orderDate = Carbon::create($tmp[0], $tmp[1], $tmp[2]);
        $tmp = explode(' ', $newDate);
        $date = explode('-', $tmp[0]);
        $time = explode(':', $tmp[1]);
        $newDate = Carbon::create($date[0], $date[1], $date[2], $time[0], $time[1], $time[2]);
        if ($newDate->format('Y-m-d') < Carbon::today()->format('Y-m-d')) {
            $this->message .= __('Transfer date of delivery earlier than today') . "\n";
        }

        if ($orderDate->diffInDays($newDate) > 10) {
            $this->message .= __('Delaying delivery dates for more than 10 days');
        }

        if (!empty($this->message)) {
            $this->message = __('Suspicious change in delivery date, order id - :id, user id - :userId, user name - :userName',
                    [
                        'id' => $order->getDisplayNumber(),
                        'userId' => \Auth::user()->id,
                        'userName' => \Auth::user()->name
                    ]
                ) . "\n" . $this->message;
            $this->sendTelegramMessage();
        }
    }

    /**
     * если переносов доставки 4 или более или суммарно доставка перенесена более чем на 10 дней
     *
     * @param Order $order
     */
    public function checkSumDateEstimateDelivery(Order $order)
    {
        $count = ModelChange::getModelsChenges(Order::class, 'date_estimated_delivery')->where('model_id', '=', $order->id)->count();
        if ($count >= 4) {
            $this->message = __('Was made :count changes date estimated delivery',
                    [
                        'count' => $count
                    ]
                ) . "\n";
        }

        $changes = ModelChange::getModelsChenges(Order::class, 'date_estimated_delivery')->where('model_id', '=', $order->id)->get();
        $difference = 0;
        /**
         * @var $change ModelChange
         */
        foreach ($changes as $change) {
            $oldDate = $change->old_values()->toArray()['date_estimated_delivery'];
            $newDate = $change->new_values()->toArray()['date_estimated_delivery'];
            if (!empty($oldDate) && !empty($newDate)) {
                $newDate = explode(' ', $newDate);
                $date = explode('-', $newDate[0]);
                $time = explode(':', $newDate[1]);
                $newDate = Carbon::create($date[0], $date[1], $date[2], $time[0], $time[1], $time[2]);
                $oldDate = explode(' ', $oldDate);
                $date = explode('-', $oldDate[0]);
                $time = explode(':', $oldDate[1]);
                $oldDate = Carbon::create($date[0], $date[1], $date[2], $time[0], $time[1], $time[2]);
                $difference += $oldDate->diffInDays($newDate);
            }
        }

        if ($difference > 10) {
            $this->message .= __('Date transfer for a total of more than 10 days, count days :count',
                [
                    'count' => $difference,
                ]
            );
        }

        if (!empty($this->message)) {
            $this->message = __('Suspicious activity related to delivery date in order, order id - :order_id, user id - :userId, user name - :userName',
                    [
                        'order_id' => $order->getDisplayNumber(),
                        'userId' => \Auth::user()->id,
                        'userName' => \Auth::user()->name
                    ]
                ) . "\n" . $this->message;
            $this->sendTelegramMessage();
        }
    }

    /**
     * Смена службы доставки в статусе подтвержден или позже
     *
     * @param Order $order
     */
    public function checkCarrier(Order $order)
    {
        if ($order->currentState()->is_confirmed || ($order->currentState()->id > OrderState::where('is_confirmed', '=', true)->first()->id)) {
            $this->message = __('Change of delivery service in status confirmed or later, order id - :id, user id - :userId, user name - :userName, order state - :state',
                [
                    'id' => $order->getDisplayNumber(),
                    'userId' => \Auth::user()->id,
                    'userName' => \Auth::user()->name,
                    'state' => $order->currentState()->name
                ]
            );

            $this->sendTelegramMessage();
        }
    }

    /**
     * каждое списание из кассы больше 1000 рублей - уведомление в чат
     *
     * @param float $quantity
     */
    public function operationQuantity(Operation $operation)
    {
        if ((float)$operation->quantity > 1000) {
            $this->message = __('Cash deduction, user id - :userId, user name - :userName, quantity :quantity. Cashbox : id cashbox - :cashbox_id, cashbox name - :cashboxname',
                [
                    'userId' => \Auth::user()->id,
                    'userName' => \Auth::user()->name,
                    'quantity' => $operation->quantity,
                    'cashbox_id' => $operation->storage_id,
                    'cashboxname' => $operation->storage->name
                ]
            );
            $this->sendTelegramMessage();
        }
    }

    /**
     * У каждой кассы должен настраиваться лимит денег и лимит одной операции. При превышении лимитов нужно присылать уведомление в чат
     *
     * @param Cashbox $cashbox
     * @param float $quantity
     */
    public function cashboxLimits(Cashbox $cashbox, float $quantity)
    {
        if ($quantity > $cashbox->operation_limit) {
            $this->message = __('Operation limit exceeded, operation limit - :limit, quantity - :quantity',
                    [
                        'limit' => $cashbox->operation_limit,
                        'quantity' => $quantity
                    ]
                ) . "\n";
        }

        foreach ($cashbox->operableIds() as $operableId) {
            $balance = $cashbox->getCurrentQuantity($operableId);
            if (($quantity + $balance) > $cashbox->limit) {
                $this->message .= __('Checkout limit exceeded cashbox limit :limit, cashbox balance :balance, operation quantity :quantity',
                    [
                        'limit' => $cashbox->limit,
                        'balance' => $balance,
                        'quantity' => $quantity,
                    ]
                );
            }
        }
        if (!empty($this->message)) {
            $this->message = __('Violation of cash limits cashbox - :name | id - :id, user id - :userId, user name - :userName',
                    [
                        'name' => $cashbox->name,
                        'id' => $cashbox->id,
                        'userId' => \Auth::user()->id,
                        'userName' => \Auth::user()->name,
                    ]
                ) . "\n" . $this->message;
            $this->sendTelegramMessage();
        }
    }

    /**
     * @param User $user
     * @param User $loginUser
     */
    public function anotherUserLogin(User $user, User $loginUser)
    {
        $this->message = __('Attempt to log into the account - :userName, id - :userId, with the token with which they logged into the account - :loggedName, id - :loggedId, ip from which the input is - :ip',
            [
                'userName' => $loginUser->name,
                'userId' => $loginUser->id,
                'loggedName' => $user->name,
                'loggedId' => $user->id,
                'ip' => \Request::ip()
            ]
        );
        $this->sendTelegramMessage();
    }

    /**
     * @param User $user
     */
    public function firedLogin(User $user)
    {
        $this->message = __('Attempting to log into a remote user account - :userName, id - :userId, ip from which the input is - :ip',
            [
                'userName' => $user->name,
                'userId' => $user->id,
                'ip' => \Request::ip()
            ]
        );
        $this->sendTelegramMessage();
    }

    /**
     * @param Task $task
     */
    public function checkTaskCloseOrder(Task $task)
    {
        $order = $task->order;
        if (!$order->currentState()->is_failure && !$order->currentState()->is_successful) {
            $this->message = __('Closing task received status completed, but order is not closed, task id - :task_id, user which close task : user id - :id, user name - :username, order id - :order_id',
                [
                    'task_id' => $task->id,
                    'id' => \Auth::id(),
                    'username' => \Auth::user()->name,
                    'order_id' => $order->getDisplayNumber(),
                ]
            );
            $this->sendTelegramMessage();
        }
    }

    /**
     * Надо сделать так, чтобы списание товара со склада без номера заказа делало алерт в безопасность
     *
     * @param Operation $operation
     */
    public function operationWithoutOrderNumber(Operation $operation)
    {
        if (!$operation->order_id && !in_array('admin', \Auth::user()->roles()->get()->pluck('name')->toArray())) {
            $this->message = __('Write-off of goods by a non-administrator user. Store : store id - :store_id, store name - :store_name. Product : product id - :product_id, product name - :product_name. User : username - :username, user id - :user_id',
                [
                    'store_id' => $operation->storage_id,
                    'store_name' => Store::find($operation->storage_id)->name,
                    'product_id' => $operation->operable_id,
                    'product_name' => Product::find($operation->operable_id)->name,
                    'username' => \Auth::user()->name,
                    'user_id' => \Auth::user()->id
                ]
            );
            $this->sendTelegramMessage();
        }
    }

    /**
     * Если присваивают пустую дату доставки
     *
     * @param Order $order
     */
    public function emptyDateEstimated(Order $order)
    {
        $this->message = __('Setting an empty delivery date. Order : order id - :order_id. User : user id - :user_id, username - :username',
            [
                'order_id' => $order->getDisplayNumber(),
                'user_id' => \Auth::user()->id,
                'username' => \Auth::user()->name,
            ]
        );
        $this->sendTelegramMessage();
    }

    /**
     * если в 23:59 на каком-то складе количество товара (считаем сумму по всем артикулам) превышает лимит (настраивается в складе) - нужно написать сообщение в чат безопасность
     *
     * @param Store $store
     */
    public function checkDailyProductLimit(Store $store)
    {
        $sum = 0;
        Product::all()->filter(function ($product) use ($store, &$sum) {
            $sum += $store->getRealCurrentQuantity($product->id);
        });

        if ($sum > $store->limit) {
            $this->message = __('Daily stock balance exceeded. Store id - :id, name - :name. Count of products - :product_count, store limit - :store_limit',
                [
                    'id' => $store->id,
                    'name' => $store->name,
                    'product_count' => $sum,
                    'store_limit' => $store->limit
                ]
            );
            $this->sendTelegramMessage();
        }
    }

    /**
     * надо в безопасность присылать уведомления, когда заказ получает статус подтвержден после статуса отказ
     *
     * @param Order $order
     */
    public function confirmedAfterFailure(Order $order, OrderState $newState)
    {
        if ($order->currentState() && $order->currentState()->is_failure && $newState->is_confirmed) {
            $this->message = __('Transfer order of status failure to status confirmed. Order : order id - :order_id. User : user id - :user_id, username - :username',
                [
                    'order_id' => $order->getDisplayNumber(),
                    'user_id' => \Auth::id(),
                    'username' => \Auth::user()->name,
                ]
            );
            $this->sendTelegramMessage();
        }
    }

    /**
     * уведомление, когда кто-то меняет цену какого-то товара
     *
     * @param Order $order
     * @param OrderDetail $orderDetail
     */
    public function changeOrderDetailPriceInOrder(Order $order, OrderDetail $orderDetail)
    {
        if ($orderDetail->getDirty()['price'] != $orderDetail->getOriginal('price')) {
            $this->message = __('Changing the price of a product in an order. Order id - :order_id, Order detail id - :order_detail_id. User id - :user_id, user name - :user_name. Old price - :old_price, new price - :new_price',
                [
                    'order_id' => $order->getDisplayNumber(),
                    'order_detail_id' => $orderDetail->id,
                    'user_id' => \Auth::id(),
                    'user_name' => \Auth::user()->name,
                    'old_price' => (int) $orderDetail->getOriginal('price'),
                    'new_price' => (int) $orderDetail->getDirty()['price'],
                ]
            );
            $this->sendTelegramMessage();
        }
    }

    /**
     * Отправка уведомления если меняют номер телефона клиента
     *
     * @param string $phone
     * @param Customer $customer
     */
    public function changeCustomerPhone(string $phone, Customer $customer)
    {
        $this->message = __('Changing customer`s phone. Customer id - :customer_id, old number - :old_number, new number - :new_number. User id - :user_id, user name - :user_name.',
            [
                'customer_id' => $customer->id,
                'old_number' => $customer->phone,
                'new_number' => $phone,
                'user_id' => \Auth::id(),
                'user_name' => \Auth::user()->name,
            ]
        );

        $this->sendTelegramMessage();
    }
}