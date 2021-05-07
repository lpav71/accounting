<?php

namespace App\Observers;

use App\Cashbox;
use App\Currency;
use App\Exceptions\DoingException;
use App\Operation;
use App\OperationState;
use App\Order;
use App\OrderDetail;
use App\OrderDetailState;
use App\OrderState;
use App\Product;
use App\ProductExchange;
use App\ProductReturn;
use App\Services\SecurityService\SecurityService;
use App\Store;
use App\VirtualOperation;
use Illuminate\Support\Str;
use Auth;
use Illuminate\Database\Eloquent\Collection;

class OrderDetailObserver
{

    /**
     * @var array
     */
    protected static $orderDetailOldAttributes = [];

    /**
     * Обработка события 'creating'
     *
     * @param OrderDetail $orderDetail
     * @throws DoingException
     * @return boolean
     */
    public function creating(OrderDetail $orderDetail)
    {
        $doingErrors = [];
        $result = true;


        if (!$orderDetail->order_id) {
            $doingErrors[] = __(
                'Order Detail should contain a reference to the order.'
            );

            $result = false;
        } else {

            switch ($orderDetail->owner_type) {
                case ProductExchange::class:

                    if (!$orderDetail->productExchange->isOpenEdit()) {
                        $doingErrors[] = __(
                            'In product exchange number :number is forbidden to add new positions.',
                            ['number' => $orderDetail->productExchange->id]
                        );

                        $result = false;
                    }

                    break;

                default:

                    if (!$orderDetail->order->isOpenEdit()) {
                        $doingErrors[] = __(
                            'In order number :orderNumber is forbidden to add new positions.',
                            ['orderNumber' => $orderDetail->order->getDisplayNumber()]
                        );

                        $result = false;
                    }

                    break;
            }

        }


        DoingException::processErrors($doingErrors);

        return $result;

    }

    /**
     * Обработка события 'created'
     *
     * @param  \App\OrderDetail $orderDetail
     * @return void
     */
    public function created(OrderDetail $orderDetail)
    {
        $orderDetail->order->comments()->create([
            'comment' => __('Added product').": {$orderDetail->product->name} - {$orderDetail->price} - {$orderDetail->currency->name} - {$orderDetail->store->name}",
            'user_id' => \Auth::id() ?: null,
        ]);
    }

    /**
     * Обработка события 'updating'
     *
     * @param  \App\OrderDetail $orderDetail
     * @return void
     * @throws DoingException
     */
    public function updating(OrderDetail $orderDetail)
    {
        $doingErrors = [];

        switch ($orderDetail->owner_type) {
            case ProductExchange::class:

                $isBlockedEdit = $orderDetail->isBlockedEditExchange();

                break;

            default:

                $isBlockedEdit = $orderDetail->isBlockedEdit();

                break;
        }

        if ($isBlockedEdit && !Auth::user()->hasPermissionTo('always-edit-order-detail')) {

            foreach ($orderDetail->getAttributes() as $key => $value) {

                if (!in_array($key, $orderDetail->getEditableAlways())
                    && $value !== $orderDetail->getOriginal($key)) {

                    $doingErrors[] = __(
                        'The following property of the product ":product" cannot be edited: :attribute.',
                        [
                            'product' => $orderDetail->product->name,
                            'attribute' => $key,
                        ]
                    );
                }

            }
        }

        DoingException::processErrors($doingErrors);

        self::$orderDetailOldAttributes[$orderDetail->id] = $orderDetail->getOriginal();
    }

    /**
     * Обработка события 'updated'
     *
     * @param  \App\OrderDetail $orderDetail
     * @return void
     */
    public function updated(OrderDetail $orderDetail)
    {
        if(in_array('price', array_keys($orderDetail->getDirty())) && $orderDetail->getOriginal('price')) {
            $service = new SecurityService();
            $service->changeOrderDetailPriceInOrder($orderDetail->order, $orderDetail);
        }

        if (self::$orderDetailOldAttributes && isset(self::$orderDetailOldAttributes[$orderDetail->id]) && is_array(self::$orderDetailOldAttributes[$orderDetail->id])) {

            $changes = [];

            $names = [
                'price' => __('Price'),
            ];

            foreach (self::$orderDetailOldAttributes[$orderDetail->id] as $name => $value) {

                if ($value != $orderDetail->$name) {

                    if (!in_array($name, array_keys($names))) {
                        switch ($name) {
                            case 'product_id':
                                $changes[] = __('Product').": '".Product::firstOrNew(['id' => $value])->name."' -> '{$orderDetail->product->name}'";
                                break;
                            case 'currency_id':
                                $changes[] = __('Currency').": '".Currency::firstOrNew(['id' => $value])->name."' -> '{$orderDetail->currency->name}'";
                                break;
                            case 'store_id':
                                $changes[] = __('Store').": '".Store::firstOrNew(['id' => $value])->name."' -> '{$orderDetail->store->name}'";
                                break;
                        }
                    } else {
                        $changes[] = "{$names[$name]}: '{$value}' -> '{$orderDetail->$name}'";
                    }
                }
            }

            if ($changes) {
                $orderDetail->order->comments()->create([
                    'comment' => __('Product changed')." '{$orderDetail->product->name}':<br>".implode('<br>',
                            $changes),
                    'user_id' => \Auth::id() ?: null,
                ]);
            }
        }

        if (isset(self::$orderDetailOldAttributes[$orderDetail->id])) {
            unset(self::$orderDetailOldAttributes[$orderDetail->id]);
        }
    }

    /**
     * Обработка события 'deleting'
     *
     * @param  \App\OrderDetail $orderDetail
     * @return void
     * @throws DoingException
     */
    public function deleting(OrderDetail $orderDetail)
    {
        $doingErrors = [];

        switch ($orderDetail->owner_type) {
            case ProductExchange::class:

                $isBlockedDelete = $orderDetail->isBlockedDeleteExchange();

                break;

            default:

                $isBlockedDelete = $orderDetail->isBlockedDelete();

                break;
        }

        if ($isBlockedDelete) {
            $doingErrors[] = __(
                'This product cannot be deleted because spent through the status block the removal: :product.',
                ['product' => $orderDetail->product->name]
            );
        }

        DoingException::processErrors($doingErrors);

        self::$orderDetailOldAttributes[$orderDetail->id] = $orderDetail->getOriginal();
    }

    /**
     * Обработка события 'deleted'
     *
     * @param  \App\OrderDetail $orderDetail
     * @return void
     */
    public function deleted(OrderDetail $orderDetail)
    {
        if (self::$orderDetailOldAttributes
            && isset(self::$orderDetailOldAttributes[$orderDetail->id])
            && is_array(self::$orderDetailOldAttributes[$orderDetail->id])) {

            $oldAttributes = self::$orderDetailOldAttributes[$orderDetail->id];
            $oldOrder = Order::where('id', (int)$oldAttributes['order_id'])->first();
            $oldProduct = Product::where('id', (int)$oldAttributes['product_id'])->first();
            $oldCurrency = Currency::where('id', (int)$oldAttributes['currency_id'])->first();
            $oldStore = Store::where('id', (int)$oldAttributes['store_id'])->first();

            $oldOrder->comments()->create([
                'comment' => __('Deleted product').": {$oldProduct->name} - {$oldAttributes['price']} - {$oldCurrency->name} - {$oldStore->name}",
                'user_id' => \Auth::id() ?: null,
            ]);
        }

        if (isset(self::$orderDetailOldAttributes[$orderDetail->id])) {
            unset(self::$orderDetailOldAttributes[$orderDetail->id]);
        }
    }

    /**
     * Обработка события 'saving'
     *
     * @param OrderDetail $orderDetail
     */
    public function saved(OrderDetail $orderDetail)
    {
        if($orderDetail->store_id != $orderDetail->getOriginal('store_id') && $orderDetail->getOriginal('store_id') != null) {
            $this->checkReservedOrderDetails($orderDetail, Store::find($orderDetail->getOriginal('store_id')));
        }
    }

    /**
     * @param OrderDetail $orderDetail
     * @param Store $store
     */
    protected function checkReservedOrderDetails(OrderDetail $orderDetail, Store $store)
    {
        $operableParentId = 0;
        $operableParentType = '';
        switch ($orderDetail->owner_type) {
            case 'App\Order':
                $operableParentId = $orderDetail->order_id;
                $operableParentType = 'order_id';
                break;
            case 'App\ProductExchange':
                $operableParentId = $orderDetail->product_exchange_id;
                $operableParentType = 'product_exchange_id';
                break;
            case 'App\ProductReturn':
                $operableParentId = $orderDetail->product_return_id;
                $operableParentType = 'product_return_id';
                break;
        }

        //Проверка если базовый продукт не композитный тогда нет смысла проверять его составные товары
        if($orderDetail->product->isComposite()) {
            $productIds = $this->getRealProductIdOrderDetail($orderDetail->product->products);
        } else {
            $productIds[] = $orderDetail->product->id;
        }

        //Сюда мы также попадаем при простом переносе, но резерва может и не быть, поэтому если резерв снимается со старого склада нужно добавлять историю
        $addHistory = false;

        //проходим циклом по всем id еденичных товаров и снимаем резерв
        foreach ($productIds as $id) {
            if($store->checkReservedQuantity($id, $operableParentType, $operableParentId)) {
                $addHistory = true;
                Operation::create(
                    array_merge(
                        [
                            'type' => 'D',
                            'comment' => __('Assembled in another warehouse') . $orderDetail->store->name ,
                            'user_id' => Auth::id(),
                            $operableParentType => $operableParentId,
                            'order_detail_id' => $orderDetail->id,
                            'is_reservation' => 1,
                            'operable_type' => Product::class,
                            'operable_id' => $id,
                            'quantity' => 1,
                            'storage_type' => Store::class,
                            'storage_id' => $store->id,
                            'is_transfer' => 1,
                        ]
                    )
                );
            }
        }

        if ($addHistory) {
            //Создаем комментарий к заказу
            $orderDetail->order->comments()->create([
                'comment' => __('The reserve has been removed from the warehouse : :old_store, reserved for store : :store',
                    ['old_store' => $store->name,
                        'store' => $orderDetail->store->name
                    ]),
                'user_id' => \Auth::id() ?: null,
            ]);
        }
    }

    /**
     * Получение реальных id товаров композитных и не композитных
     *
     * @param Collection $products
     * @return array
     */
    protected function getRealProductIdOrderDetail(Collection $products)
    {
        $ids = [];
        /**
         * @var $product Product
         */
        foreach ($products as $product) {
            if($product->isComposite()) {
                foreach ($this->getRealProductIdOrderDetail($product->products) as $productId) {
                    $ids[] = $productId;
                }
            } else {
                $ids[] = $product->id;
            }
        }

        return $ids;
    }

    /**
     * Обработка события 'belongsToManyAttaching'
     *
     * @param string $relation
     * @param OrderDetail $orderDetail
     * @param array $ids
     * @return bool
     * @throws DoingException
     */
    public function belongsToManyAttaching(string $relation, OrderDetail $orderDetail, array $ids, array $attributes)
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
                    $orderDetail->states()->attach($ids, $attributes);
                    break;
                }

                $newState = OrderDetailState::find(reset($ids));

                if ($orderDetail->currentState() && $orderDetail->currentState()->id == $newState->id) {
                    $result = false;

                    break;
                }


                if (!$orderDetail->currentState() && $newState->previousStates->isNotEmpty()) {
                    $doingErrors[] = __(
                        'The status ":state" may not be the first status of position.',
                        ['state' => $newState->name]
                    );
                    break;
                }

                if ($orderDetail->currentState() && !$newState->previousStates->find($orderDetail->currentState()->id)) {
                    $doingErrors[] = __(
                        'Change of status from ":oldState" to ":newState" is not possible.',
                        [
                            'oldState' => $orderDetail->currentState()->name,
                            'newState' => $newState->name,
                        ]
                    );
                    break;
                }

                if (in_array($newState->new_order_detail_owner_type, array_keys(OrderDetail::OWNERS))) {

                    $ownerId = Str::snake(str_replace('App\\', '', $newState->new_order_detail_owner_type)).'_id';

                    if (!$orderDetail->$ownerId) {
                        $doingErrors[] = __(
                            'Error of change of status from ":oldState" to ":newState". Can not change owner for Order Detail.',
                            [
                                'oldState' => $orderDetail->currentState()->name,
                                'newState' => $newState->name,
                            ]
                        );

                        break;
                    }

                    $orderDetail->update([
                        'owner_type' => $newState->new_order_detail_owner_type,
                    ]);
                }

                $this->createStoreOperation($newState->store_operation, $orderDetail);

                $ownerId = Str::snake(str_replace('App\\', '', $orderDetail->owner_type)).'_id';

                switch ($newState->currency_operation_by_order) {
                    case 'C':

                        VirtualOperation::create([
                            'type' => 'C',
                            'storage_type' => Order::class,
                            'storage_id' => $orderDetail->order->id,
                            'operable_type' => Currency::class,
                            'operable_id' => $orderDetail->currency->id,
                            'owner_type' => $orderDetail->owner_type,
                            'owner_id' => $orderDetail->$ownerId,
                            'quantity' => $orderDetail->price,
                            'user_id' => \Auth::id() ?: null,
                            'is_reservation' => 0,
                            'comment' => __('Reduction of customer debt'),
                        ]);

                        break;
                    case 'D':

                        VirtualOperation::create([
                            'type' => 'D',
                            'storage_type' => Order::class,
                            'storage_id' => $orderDetail->order->id,
                            'operable_type' => Currency::class,
                            'operable_id' => $orderDetail->currency->id,
                            'owner_type' => $orderDetail->owner_type,
                            'owner_id' => $orderDetail->$ownerId,
                            'quantity' => $orderDetail->price,
                            'user_id' => \Auth::id() ?: null,
                            'is_reservation' => 0,
                            'comment' => __('The increase in the debt of the customer'),
                        ]);

                        break;
                }

                switch ($newState->product_operation_by_order) {
                    case 'C':

                        VirtualOperation::create([
                            'type' => 'C',
                            'storage_type' => Order::class,
                            'storage_id' => $orderDetail->order->id,
                            'operable_type' => Product::class,
                            'operable_id' => $orderDetail->product->id,
                            'owner_type' => $orderDetail->owner_type,
                            'owner_id' => $orderDetail->$ownerId,
                            'quantity' => 1,
                            'user_id' => \Auth::id() ?: null,
                            'is_reservation' => 0,
                            'comment' => __('Receiving from the customer or include to return'),
                        ]);

                        break;
                    case 'D':

                        VirtualOperation::create([
                            'type' => 'D',
                            'storage_type' => Order::class,
                            'storage_id' => $orderDetail->order->id,
                            'operable_type' => Product::class,
                            'operable_id' => $orderDetail->product->id,
                            'owner_type' => $orderDetail->owner_type,
                            'owner_id' => $orderDetail->$ownerId,
                            'quantity' => 1,
                            'user_id' => \Auth::id() ?: null,
                            'is_reservation' => 0,
                            'comment' => __('Transfer to the customer or exclude from return'),
                        ]);

                        break;
                }

                break;
        }

        DoingException::processErrors($doingErrors);

        return $result;
    }

    /**
     * Обработка события 'belongsToManyAttached'
     *
     * @param string $relation
     * @param OrderDetail $orderDetail
     * @param array $ids
     * @return bool
     * @throws DoingException
     */
    public function belongsToManyAttached(string $relation, OrderDetail $orderDetail, array $ids)
    {
        $doingErrors = [];
        $result = true;

        switch ($relation) {
            case 'states':

                $orderDetail->order->comments()->create([
                    'comment' => __('Product changed')." '{$orderDetail->product->name}': {$orderDetail->currentState()->name}",
                    'user_id' => \Auth::id() ?: null,
                ]);

                break;
        }

        DoingException::processErrors($doingErrors);

        try {
            $this->checkAutoCloseOrder($orderDetail->order);
        } catch (\Exception $exception) {
            \Session::flash('warning', $exception->getMessage());
        }

        $this->createCertificatesOperations($orderDetail);

        return $result;
    }

    /**
     * @param OrderDetail $orderDetail
     */
    protected function createCertificatesOperations(OrderDetail $orderDetail): void
    {
        if ($orderDetail->product->category->is_certificate) {
            if($orderDetail->currentState()->crediting_certificate) {
                $orderDetail->certificate->creditingMoney();
            }
            if($orderDetail->currentState()->writing_off_certificate) {
                $orderDetail->certificate->writingOffMoney($orderDetail->certificate->getBalance(), $orderDetail->order);
            }
            if($orderDetail->currentState()->zeroing_certificate_number) {
                $orderDetail->certificate->softDelete($orderDetail);
            }
        }
    }

    /**
     * Проверка возможности автозакрытия заказа (если все товары в статусе доставле/возвращен)
     *
     * @param Order $order
     * @return bool|int
     */
    protected function checkAutoCloseOrder(Order $order)
    {
        $autoCloseFlag = true;
        $statuses = [];
        /**
         * @var $detail OrderDetail
         */
        foreach ($order->orderDetails()->where('is_exchange', 0)->get() as $detail) {
            if(($detail->currentState()->is_delivered || $detail->currentState()->is_returned) && (!$detail->currentState()->is_courier_state) && ($detail->currentState()->owner_type == 'App\Order')) {
                if($detail->currentState()->is_delivered) {
                    $statuses[] = 1;
                } else {
                    $statuses[] = 0;
                }
            } else {
                $autoCloseFlag = false;
                break;
            }
        }

        if (!$autoCloseFlag) {
            return false;
        }
        if (!$order->currentState()->is_successful || !$order->currentState()->is_failure) {
            if (in_array(1, $statuses)) {
                $order->states()->save(OrderState::whereIsSuccessful(1)->first());
                return true;
            } else {
                $order->states()->save(OrderState::whereIsFailure(1)->first());
                return true;
            }
        }
    }

    /**
     * Создание складской операции
     *
     * @param string $storeOperation
     * @param OrderDetail $orderDetail
     */
    protected function createStoreOperation($storeOperation, OrderDetail $orderDetail)
    {

        switch ($storeOperation) {
            case 'C':

                Operation::create([
                    'type' => 'C',
                    'quantity' => 1,
                    'comment' => __('Shipping by order'),
                    'user_id' => \Auth::id() ?: null,
                    'order_id' => $orderDetail->order->id,
                    'order_detail_id' => $orderDetail->id,
                    'is_reservation' => 0,
                    'operable_type' => Product::class,
                    'operable_id' => $orderDetail->product->id,
                    'storage_type' => Store::class,
                    'storage_id' => $orderDetail->store->id,
                ]);

                break;
            case 'CR':

                Operation::create([
                    'type' => 'C',
                    'quantity' => 1,
                    'comment' => __('Reservation by order'),
                    'user_id' => \Auth::id() ?: null,
                    'order_id' => $orderDetail->order->id,
                    'order_detail_id' => $orderDetail->id,
                    'is_reservation' => 1,
                    'operable_type' => Product::class,
                    'operable_id' => $orderDetail->product->id,
                    'storage_type' => Store::class,
                    'storage_id' => $orderDetail->store->id,
                ]);

                break;
            case 'D':

                Operation::create([
                    'type' => 'D',
                    'quantity' => 1,
                    'comment' => __('Return by order'),
                    'user_id' => \Auth::id() ?: null,
                    'order_id' => $orderDetail->order->id,
                    'order_detail_id' => $orderDetail->id,
                    'is_reservation' => 0,
                    'operable_type' => Product::class,
                    'operable_id' => $orderDetail->product->id,
                    'storage_type' => Store::class,
                    'storage_id' => $orderDetail->store->id,
                ]);

                break;
            case 'DR':

                Operation::create([
                    'type' => 'D',
                    'quantity' => 1,
                    'comment' => __('Cancel reservation by order'),
                    'user_id' => \Auth::id() ?: null,
                    'order_id' => $orderDetail->order->id,
                    'order_detail_id' => $orderDetail->id,
                    'is_reservation' => 1,
                    'operable_type' => Product::class,
                    'operable_id' => $orderDetail->product->id,
                    'storage_type' => Store::class,
                    'storage_id' => $orderDetail->store->id,
                ]);

                break;
        }
    }
}
