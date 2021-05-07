<?php

namespace App\Observers;

use App\Exceptions\DoingException;
use App\VirtualOperation;

class VirtualOperationObserver
{
    protected $operationTypes = [];

    public function __construct()
    {
        $this->operationTypes = VirtualOperation::OPERATION_TYPES;
    }

    /**
     * Обработка события 'creating'
     *
     * @param VirtualOperation $operation
     * @throws DoingException
     * @return boolean
     */
    public function creating(VirtualOperation $operation)
    {
        $doingErrors = [];
        $result = true;

        if (isset($this->operationTypes[$operation->type])) {
            $methodName =
                "creating{$this->operationTypes[$operation->type]}"
                .($operation->is_reservation ? 'Reservation' : '')
                .ucfirst(str_replace('App\\', '', $operation->storage_type))
                .ucfirst(str_replace('App\\', '', $operation->operable_type))
                .(
                    (ucfirst(str_replace('App\\', '', $operation->owner_type)) ?: 'No')
                    .'Owner'
                )
                ."Operation";

            if (method_exists($this, $methodName)) {
                $result = $this->$methodName($operation, $doingErrors);
            } else {
                $doingErrors[] = __(
                    'There is no method ":method" of the VirtualOperationObserver to handle the create operation. The creation of operations without treatment is prohibited.',
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
     * @param VirtualOperation $operation
     * @throws DoingException
     * @return false
     */
    public function updating(VirtualOperation $operation)
    {
        $doingErrors = [
            __('Update operations are not allowed.'),
        ];

        DoingException::processErrors($doingErrors);

        return false;
    }

    /**
     * Обработка события 'deleting'
     *
     * @param VirtualOperation $operation
     * @throws DoingException
     * @return false
     */
    public function deleting(VirtualOperation $operation)
    {
        $doingErrors = [
            __('Delete operations are not allowed.'),
        ];

        DoingException::processErrors($doingErrors);

        return false;
    }

    /**
     * Обработка события 'restoring'
     *
     * @param VirtualOperation $operation
     * @throws DoingException
     * @return false
     */
    public function restoring(VirtualOperation $operation)
    {
        $doingErrors = [
            __('Delete operations are not allowed.'),
        ];

        DoingException::processErrors($doingErrors);

        return false;
    }

    /**
     * Обработка события 'creating' для Кредитовой операции с Валютой по Заказу (Владелец операции - Заказ)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingCreditOrderCurrencyOrderOwnerOperation(VirtualOperation $operation, array &$doingErrors)
    {

        return true;
    }

    /**
     * Обработка события 'creating' для Дебетовой операции с Валютой по Заказу (Владелец операции - Заказ)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingDebitOrderCurrencyOrderOwnerOperation(VirtualOperation $operation, array &$doingErrors)
    {

        return true;
    }

    /**
     * Обработка события 'creating' для Кредитовой операции с Валютой по Заказу (Владелец операции - Возврат)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingCreditOrderCurrencyProductReturnOwnerOperation(
        VirtualOperation $operation,
        array &$doingErrors
    ) {

        return true;
    }

    /**
     * Обработка события 'creating' для Дебетовой операции с Валютой по Заказу (Владелец операции - Возврат)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingDebitOrderCurrencyProductReturnOwnerOperation(
        VirtualOperation $operation,
        array &$doingErrors
    ) {

        return true;
    }

    /**
     * Обработка события 'creating' для Кредитовой операции с Валютой по Заказу (Владелец операции - Обмен)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingCreditOrderCurrencyProductExchangeOwnerOperation(
        VirtualOperation $operation,
        array &$doingErrors
    ) {

        return true;
    }

    /**
     * Обработка события 'creating' для Дебетовой операции с Валютой по Заказу (Владелец операции - Обмен)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingDebitOrderCurrencyProductExchangeOwnerOperation(
        VirtualOperation $operation,
        array &$doingErrors
    ) {

        return true;
    }

    /**
     * Обработка события 'creating' для Кредитовой операции с Товаром по Заказу (Владелец операции - Заказ)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingCreditOrderProductOrderOwnerOperation(VirtualOperation $operation, array &$doingErrors)
    {

        return true;
    }

    /**
     * Обработка события 'creating' для Дебетовой операции с Товаром по Заказу (Владелец операции - Заказ)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingDebitOrderProductOrderOwnerOperation(VirtualOperation $operation, array &$doingErrors)
    {

        return true;
    }

    /**
     * Обработка события 'creating' для Кредитовой операции с Товаром по Заказу (Владелец операции - Возврат)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingCreditOrderProductProductReturnOwnerOperation(
        VirtualOperation $operation,
        array &$doingErrors
    ) {

        return true;
    }

    /**
     * Обработка события 'creating' для Дебетовой операции с Товаром по Заказу (Владелец операции - Возврат)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingDebitOrderProductProductReturnOwnerOperation(
        VirtualOperation $operation,
        array &$doingErrors
    ) {

        return true;
    }

    /**
     * Обработка события 'creating' для Кредитовой операции с Товаром по Заказу (Владелец операции - Обмен)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingCreditOrderProductProductExchangeOwnerOperation(
        VirtualOperation $operation,
        array &$doingErrors
    ) {

        return true;
    }

    /**
     * Обработка события 'creating' для Дебетовой операции с Товаром по Заказу (Владелец операции - Обмен)
     *
     * @param VirtualOperation $operation
     * @param array $doingErrors
     * @return boolean
     */
    protected function creatingDebitOrderProductProductExchangeOwnerOperation(
        VirtualOperation $operation,
        array &$doingErrors
    ) {

        return true;
    }
}
