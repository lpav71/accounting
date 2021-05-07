<?php

namespace App\Observers;

use App\Cashbox;
use App\Currency;
use App\Exceptions\DoingException;
use App\Operation;
use App\Order;
use App\Product;
use App\ProductReturn;
use App\Services\SecurityService\SecurityService;
use App\Store;
use App\VirtualOperation;

class OperationObserver
{
    protected $operationTypes = [];

    public function __construct()
    {
        $this->operationTypes = Operation::OPERATION_TYPES;
    }

    /**
     * Обработка события 'creating'
     *
     * @param Operation $operation
     * @throws DoingException
     * @return boolean
     */
    public function creating(Operation $operation)
    {
        $doingErrors = [];
        $result = true;


        if (isset($this->operationTypes[$operation->type])) {
            $methodName =
                "creating{$this->operationTypes[$operation->type]}"
                .($operation->is_reservation ? 'Reservation' : '')
                .ucfirst(str_replace('App\\', '', $operation->storage_type))
                .ucfirst(str_replace('App\\', '', $operation->operable_type))
                ."Operation";

            if (method_exists($this, $methodName)) {
                $result = $this->$methodName($operation, $doingErrors);
            } else {
                $doingErrors[] = __(
                    'There is no method ":method" of the OperationObserver to handle the create operation. The creation of operations without treatment is prohibited.',
                    ['method' => $methodName]
                );
            }
        } else {
            $doingErrors[] = __('Invalid operation type.');
        }

        DoingException::processErrors($doingErrors);

        return $result;

    }

    /**
     * Обработка события 'updating'
     *
     * @param Operation $operation
     * @throws DoingException
     */
    public function updating(Operation $operation)
    {

        $doingErrors = [
            __('Update operations are not allowed.'),
        ];

        $operation->syncOriginal();

        DoingException::processErrors($doingErrors);
    }

    /**
     * Обработка события 'deleting'
     *
     * @param Operation $operation
     * @throws DoingException
     */
    public function deleting(Operation $operation)
    {

        $doingErrors = [
            __('Delete operations are not allowed.'),
        ];

        $operation->syncOriginal();

        DoingException::processErrors($doingErrors);
    }

    /**
     * Обработка события 'restoring'
     *
     * @param Operation $operation
     * @throws DoingException
     */
    public function restoring(Operation $operation)
    {

        $doingErrors = [
            __('Delete operations are not allowed.'),
        ];

        $operation->syncOriginal();

        DoingException::processErrors($doingErrors);
    }

    /**
     * Обработка события 'creating' для Кредитовой операции с Валютой по Кассе
     *
     * @param Operation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingCreditCashboxCurrencyOperation(Operation $operation, array &$doingErrors)
    {
        $service = new SecurityService();
        /**
         * @var Currency $currency
         */
        $currency = $operation->operable;

        /**
         * @var Cashbox $cashbox
         */
        $cashbox = $operation->storage;
        $service->cashboxLimits($cashbox, $operation->quantity);
        $service->operationQuantity($operation);

        if ($operation->is_transfer) {

            if (!in_array(\Auth::id(), $cashbox->usersWithTransferRights()->pluck('user_id')->toArray())) {
                $doingErrors[] = __(
                    'You have no rights to the transfer operation for :cashbox',
                    [
                        'cashbox' => $cashbox->name,
                    ]
                );

                DoingException::processErrors($doingErrors);
            }
        }

        $currentQuantity = $cashbox->getCurrentQuantity($currency->id);

        if ($operation->quantity > $currentQuantity) {
            $doingErrors[] = __(
                'The amount of the credit operation for the cash ":cashbox" in currency ":currency" exceeds the current balance. Current balance: :current.',
                [
                    'currency' => $currency->name,
                    'cashbox' => $cashbox->name,
                    'current' => $currentQuantity,
                ]
            );
        }

        if ($operation->order_id) {
            VirtualOperation::create(
                [
                    'type' => 'D',
                    'storage_type' => Order::class,
                    'storage_id' => $operation->order_id,
                    'operable_type' => Currency::class,
                    'operable_id' => $operation->operable_id,
                    'owner_type' => $operation->product_return_id ? ProductReturn::class : Order::class,
                    'owner_id' => $operation->product_return_id ? $operation->product_return_id : $operation->order_id,
                    'quantity' => $operation->quantity,
                    'user_id' => \Auth::id() ?: null,
                    'is_reservation' => 0,
                    'comment' => __('The increase in the debt of the customer'),
                ]
            );
        }

        return true;

    }

    /**
     * Обработка события 'creating' для Дебетовой операции с Валютой по Кассе
     *
     * @param Operation $operation
     * @param array $doingErrors
     * @return bool
     * @throws DoingException
     */
    protected function creatingDebitCashboxCurrencyOperation(Operation $operation, array &$doingErrors)
    {
        $service = new SecurityService();
        $cashbox = $operation->storage;

        $service->cashboxLimits($cashbox, $operation->quantity);

        if (!in_array(\Auth::id(), $cashbox->users()->pluck('user_id')->toArray())) {
            $doingErrors[] = __(
                'You have no rights for operation for :cashbox',
                [
                    'cashbox' => $cashbox->name,
                ]
            );

            DoingException::processErrors($doingErrors);
        }

        if ($operation->order_id) {
            VirtualOperation::create(
                [
                    'type' => 'C',
                    'storage_type' => Order::class,
                    'storage_id' => $operation->order_id,
                    'operable_type' => Currency::class,
                    'operable_id' => $operation->operable_id,
                    'owner_type' => $operation->product_return_id ? ProductReturn::class : Order::class,
                    'owner_id' => $operation->product_return_id ? $operation->product_return_id : $operation->order_id,
                    'quantity' => $operation->quantity,
                    'user_id' => \Auth::id() ?: null,
                    'is_reservation' => 0,
                    'comment' => __('Reduction of customer debt'),
                ]
            );
        }

        return true;
    }

    /**
     * Обработка события 'creating' для Кредитовой операции с Товаром по Складу
     *
     * @param Operation $operation
     * @param array $doingErrors
     * @throws DoingException
     * @return boolean
     */
    protected function creatingCreditStoreProductOperation(Operation $operation, array &$doingErrors)
    {
        /**
         * @var Product $product
         */
        $product = $operation->operable;

        /**
         * @var Store $store
         */
        $store = $operation->storage;

        if ($operation->is_transfer) {

            if (!in_array(\Auth::id(), $store->usersWithTransferRights()->pluck('user_id')->toArray())) {
                $doingErrors[] = __(
                    'You have no rights to the transfer operation for :store store.',
                    [
                        'store' => $store->name,
                    ]
                );

                DoingException::processErrors($doingErrors);
            }

        } else {
            if (!in_array(\Auth::id(), $store->usersWithOperationRights()->pluck('user_id')->toArray())) {
                $doingErrors[] = __(
                    'You have no rights to the operation for :store store.',
                    [
                        'store' => $store->name,
                    ]
                );

                DoingException::processErrors($doingErrors);
            }
        }

        if ($this->runStoreProductCompositeOperationOrFail($operation, $store, $product)) {
            return false;
        }

        $ownReservedQuantity = $this->getReservedQuantityByAllAttributes($operation);
        $currentQuantity = $store->getCurrentQuantity($product->id) + $ownReservedQuantity;

        if ($operation->quantity > $currentQuantity) {
            $reservedOrders = $this->getOrderReservedProduct($operation);
            $orders = '';
            foreach ($reservedOrders as $order) {
                $orders .= "<a class ='text-dark' href='" . route('orders.edit', $order->id) . "'>" . $order->getDisplayNumber() . "</a>, ";
            }

            $doingErrors[] = __(
                'The amount of the product :product in stock :store is less than the required for the operation. Current quantity: :quantity.',
                [
                    'product' => $product->name,
                    'store' => $store->name,
                    'quantity' => $currentQuantity,
                ]
            );
            $doingErrors[] = __('Orders in which this product is reserved :orders', ['orders' => $orders]);

            DoingException::processErrors($doingErrors);
        }

        if ($ownReservedQuantity > 0) {

            Operation::create(
                array_merge(
                    $operation->getAttributes(),
                    [
                        'type' => 'D',
                        'comment' => __(
                                'Withdrawal of reserve for the subsequent operation: '
                            )."'{$operation->comment}'",
                        'quantity' => min($ownReservedQuantity, $operation->quantity),
                        'is_reservation' => 1,
                    ]
                )
            );

        }

        return true;

    }

    /**
     * Получение заказов по которому есть резерв
     *
     * @param Operation $operation
     * @return \Illuminate\Support\Collection
     */
    protected function getOrderReservedProduct(Operation $operation)
    {
        $creditQuery = Operation::query()
            ->where('is_reservation', 1)
            ->where('type', 'C')
            ->where('operable_id', $operation->operable_id)
            ->where('storage_id', $operation->storage_id)
            ->get();

        $orders = collect();
        foreach ($creditQuery as $operation) {
            $debet = Operation::query()
                ->where('is_reservation', 1)
                ->where('type', 'D')
                ->where('operable_id', $operation->operable_id)
                ->where('order_id', $operation->order_id)
                ->where('storage_id', $operation->storage_id)
                ->get();

            if (!empty($debet)) {
                $orders->push($operation->order);
            }

        }

        return $orders;
    }

    /**
     * Обработка события 'creating' для Кредитовой операции резерва с Товаром по Складу
     *
     * @param Operation $operation
     * @param array $doingErrors
     * @return boolean
     * @throws DoingException
     */
    protected function creatingCreditReservationStoreProductOperation(Operation $operation, array &$doingErrors)
    {
        /**
         * @var Product $product
         */
        $product = $operation->operable;

        /**
         * @var Store $store
         */
        $store = $operation->storage;

        if (!in_array(\Auth::id(), $store->usersWithReservationRights()->pluck('user_id')->toArray())) {
            $doingErrors[] = __(
                'You have no rights to the reservation for :store store.',
                [
                    'store' => $store->name,
                ]
            );

            DoingException::processErrors($doingErrors);
        }

        if ($this->runStoreProductCompositeOperationOrFail($operation, $store, $product)) {
            return false;
        }

        $currentQuantity = $store->getCurrentQuantity($product->id);

        if ($operation->quantity > $currentQuantity) {
            $doingErrors[] = __(
                'The amount of the product ":product" in stock ":store" is less than the required for the operation. Current quantity: :quantity.',
                [
                    'product' => $product->name,
                    'store' => $store->name,
                    'quantity' => $currentQuantity,
                ]
            );
        }

        return true;
    }

    /**
     * Обработка события 'creating' для Дебетовой операции с Товаром по Складу
     *
     * @param Operation $operation
     * @param array $doingErrors
     * @return boolean
     * @throws DoingException
     */
    protected function creatingDebitStoreProductOperation(Operation $operation, array &$doingErrors)
    {
        /**
         * @var Product $product
         */
        $product = $operation->operable;

        /**
         * @var Store $store
         */
        $store = $operation->storage;

        if ($operation->is_transfer) {

            if (!in_array(\Auth::id(), $store->usersWithTransferRights()->pluck('user_id')->toArray())) {
                $doingErrors[] = __(
                    'You have no rights to the transfer operation for :store store.',
                    [
                        'store' => $store->name,
                    ]
                );

                DoingException::processErrors($doingErrors);
            }

        } else {
            if (!in_array(\Auth::id(), $store->usersWithOperationRights()->pluck('user_id')->toArray())) {
                $doingErrors[] = __(
                    'You have no rights to the operation for :store store.',
                    [
                        'store' => $store->name,
                    ]
                );

                DoingException::processErrors($doingErrors);
            }
        }

        if ($operation->is_transfer && !is_null($operation->orderDetail)) {
            $operation->orderDetail->update(['store_id' => $store->id]);
        }

        if ($this->runStoreProductCompositeOperationOrFail($operation, $store, $product)) {
            return false;
        }

        return true;
    }

    /**
     * Обработка события 'creating' для Дебетовой операции резерва с Товаром по Складу
     *
     * @param Operation $operation
     * @param array $doingErrors
     * @return boolean
     * @throws DoingException
     */
    protected function creatingDebitReservationStoreProductOperation(Operation $operation, array &$doingErrors)
    {
        /**
         * @var Product $product
         */
        $product = $operation->operable;

        /**
         * @var Store $store
         */
        $store = $operation->storage;

        if (!in_array(\Auth::id(), $store->usersWithReservationRights()->pluck('user_id')->toArray())) {
            $doingErrors[] = __(
                'You have no rights to the reservation for :store store.',
                [
                    'store' => $store->name,
                ]
            );

            DoingException::processErrors($doingErrors);
        }

        if ($this->runStoreProductCompositeOperationOrFail($operation, $store, $product)) {
            return false;
        }

        $operation->quantity = min($operation->quantity, $this->getReservedQuantityByAllAttributes($operation));

        if ($operation->quantity == 0) {
            return false;
        }

        return true;
    }

    /**
     * Получение текущего остатка по операциям резерва для уникальной подписи предмета операции (по атрибутам)
     *
     * @param Operation $operation
     * @return float|int
     */
    protected function getReservedQuantityByAllAttributes(Operation $operation)
    {
        $attributes = [
            'operable_id' => 'int',
            'operable_type' => 'string',
            'storage_id' => 'int',
            'storage_type' => 'string',
            'order_id' => 'int',
            'order_detail_id' => 'int',
        ];

        $creditQuery = Operation::query()
            ->where('is_reservation', 1)
            ->where('type', 'C');

        $debitQuery = Operation::query()
            ->where('is_reservation', 1)
            ->where('type', 'D');

        foreach ($attributes as $name => $type) {
            $value = ($type == 'int' ? (int)$operation->$name : (string)$operation->$name);

            $creditQuery = $creditQuery->where($name, $value);
            $debitQuery = $debitQuery->where($name, $value);
        }

        $creditQuantity = $creditQuery->get()->sum('quantity');
        $debitQuantity = $debitQuery->get()->sum('quantity');

        //////////////////////////////////////
        return ($debitQuantity - $creditQuantity) * -1;
    }

    /**
     * Разбиение складских операции по товарам, входящим в составной товар
     *
     * @param Operation $operation
     * @param Store $store
     * @param Product $product
     * @return bool
     */
    protected function runStoreProductCompositeOperationOrFail(Operation $operation, Store $store, Product $product)
    {
        if ($product->isComposite()) {

            $operationAttributes = $operation->getAttributes();

            $product->products->each(
                function (Product $product) use ($store, $operationAttributes) {
                    Operation::create(
                        array_merge(
                            $operationAttributes,
                            ['operable_id' => $product->id]
                        )
                    );
                }
            );

            return true;
        }

        return false;
    }
}
