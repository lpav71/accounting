<?php

namespace App\Services\CalculateExpenseService;

use App\OrderDetail;
use Throwable;

class ExpenseException extends \Exception
{
    /**
     * Объект в котором возникла ошибка
     *
     * @var OrderDetail
     */
    private $object;

    public function __construct(OrderDetail $object, $code = 0)
    {
        parent::__construct(__('Processing error'), $code);
        $this->object = $object;
    }

    /**
     * @return string
     */
    public function info() :string
    {
        return __('Order :order, orderDetail :orderDetail',['order' => $this->object->order->id, 'orderDetail' => $this->object->id]);
    }
}