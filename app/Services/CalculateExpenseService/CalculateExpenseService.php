<?php

namespace App\Services\CalculateExpenseService;

use App\Carrier;
use App\Category;
use App\Configuration;
use App\ExpenseSettings;
use App\Manufacturer;
use App\Order;
use App\OrderDetail;
use App\OrderState;
use App\Product;
use ChrisKonnertz\StringCalc\StringCalc;
use Illuminate\Support\Collection;
use function GuzzleHttp\Psr7\str;

class CalculateExpenseService
{
    const REVENUE = '{$revenue}';
    const SELF_VALUE = '{$self_value}';

    private $calc;
    private $todayCourse;
    private $cmpCategories;
    private $successful_states;
    private $minimal_states;

    /**
     * CalculateExpenseService constructor.
     * @param int $todayCourse
     * @param int $cmpCategories
     * @throws \ChrisKonnertz\StringCalc\Exceptions\ContainerException
     */
    public function __construct(int $todayCourse = 0, int $cmpCategories = 0)
    {
        $this->calc = new StringCalc();
        $this->todayCourse = $todayCourse;
        $this->cmpCategories = $cmpCategories;
        $configuration = Configuration::all()->where(
            'name',
            'settings_order_detail_states_for_expenses'
        )->first();
        $values = $configuration ? json_decode($configuration->values) : [];
        $this->successful_states = $values->successful_states ?? [];
        $this->minimal_states = is_object($values) && isset($values->minimal_states) ? $values->minimal_states : [];
    }

    /**
     * @param Order $order
     * @param float $revenue
     * @return array
     * @throws \ChrisKonnertz\StringCalc\Exceptions\ContainerException
     * @throws \ChrisKonnertz\StringCalc\Exceptions\NotFoundException
     */
    public function calculateOrder(Order $order)
    {
        if (count($order->orderDetails)) {
            try {
                //Получаем категорию для заказа, которую будем использовать при расчете
                $category = $this->getCategory($order);

                //Получаем товар для заказа, который будем использововать при расчете
                $product = $this->getManufacturer($order, $category->is_watch);

                $product = $product->product;

                $orderAttributes = $this->whichOrderDetailBought($order);

                if (!$orderAttributes['revenue']) {
                    $orderAttributes['revenue'] = 0;
                    $orderAttributes['self_value'] = 0;
                }

                $expenses = ExpenseSettings::all()->filter(function (ExpenseSettings $expenseSettings) use ($category, $product, $order) {
                    //Заказы берем только если дошло до статуса подтверждено
                    if($order->currentState()->is_new) {
                        return false;
                    }

                    //Проверка методов доставки
                    if ($expenseSettings->carriers->isNotEmpty()) {
                        $carrier = $expenseSettings->carriers->search(function (Carrier $carrier) use ($order) {
                            return $carrier->id === $order->carrier_id;
                        });
                        if ($carrier === false) {
                            return false;
                        }
                    }

                    //Проверка брендов
                    if ($expenseSettings->manufacturters->isNotEmpty()) {
                        $manufacturer = $expenseSettings->manufacturters->search(function (Manufacturer $manufacturer) use ($product) {
                            return $manufacturer->id === $product->manufacturer->id;
                        });
                        if ($manufacturer === false) {
                            return false;
                        }
                    }

                    //Проверка utm
                    if ($expenseSettings->utm_campaign_id != 0) {
                        if ($order->utm) {
                            if ($expenseSettings->utm_campaign_id != $order->utm->utm_campaign_id) {
                                return false;
                            }
                        } else {
                            return false;
                        }
                    }

                    //Проверка категорий
                    if (($expenseSettings->category_id != 0) && ($expenseSettings->category_id != $category->id)) {
                        return false;
                    }

                    //Проверка статусов заказов
                    if($expenseSettings->orderStates->isNotEmpty()) {
                        if(!in_array($order->currentState()->id, $expenseSettings->orderStates()->pluck('id')->toArray())) {
                            return false;
                        }
                    }

                    //Проверка источников
                    if($expenseSettings->channels->isNotEmpty()) {
                        if(!in_array($order->channel->id, $expenseSettings->channels()->pluck('id')->toArray())) {
                            return false;
                        }
                    }

                    return true;
                });

                return ['expense' => $this->getCurrentSum($expenses, $orderAttributes['revenue'], $orderAttributes['self_value']), 'revenue' => $orderAttributes['revenue']];
            } catch (ExpenseException $exception) {
                \Session::flash('warning', $exception->getMessage() . ' ' . $exception->info());
                return [];
            }
        } else {
            return [];
        }
    }

    /**
     * Получение категории по которой будут считатся расходы на заказ
     *
     * @param Order $order
     * @return Category|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     * @throws ExpenseException
     */
    protected function getCategory(Order $order)
    {
        /**
         * @var $orderDetail OrderDetail
         */
        foreach ($order->orderDetails as $orderDetail) {
            if(!$orderDetail->product->category) {
                throw new ExpenseException($orderDetail,1 );
            }
            if ($orderDetail->product->category->is_watch) {
                return $orderDetail->product->category;
            }
        }

        return Category::where(['is_accessory' => 1])->first();
    }

    /**
     * Получение бренда самого дешевого товара, для рассчета расхода
     *
     * @param Order $order
     * @param bool $is_watch
     * @return Product
     * @throws ExpenseException
     */
    protected function getManufacturer(Order $order, bool $is_watch): OrderDetail
    {
        $minCostOrderDetail = null;
        /**
         * @var $orderDetail OrderDetail
         */
        foreach ($order->orderDetails as $orderDetail) {
            if(!$orderDetail->product->category) {
                throw new ExpenseException($orderDetail,2);
            }
            if ($is_watch) {
                if ($orderDetail->product->category->is_watch) {
                    /**
                     * @var $minCostOrderDetail OrderDetail
                     */
                    if (is_object($minCostOrderDetail)) {
                        if ($minCostOrderDetail->price > $orderDetail->price) {
                            $minCostOrderDetail = $orderDetail;
                        }
                    } else {
                        $minCostOrderDetail = $orderDetail;
                    }
                }
            } elseif ($orderDetail->product->category->is_expense_accessory) {
                /**
                 * @var $minCostOrderDetail OrderDetail
                 */
                if (is_object($minCostOrderDetail)) {
                    if ($minCostOrderDetail->price > $orderDetail->price) {
                        $minCostOrderDetail = $orderDetail;
                    }
                } else {
                    $minCostOrderDetail = $orderDetail;
                }
            }
        }

        if ($minCostOrderDetail == null) {
            foreach ($order->orderDetails as $orderDetail) {
                if (is_object($minCostOrderDetail)) {
                    if ($minCostOrderDetail->price > $orderDetail->price) {
                        $minCostOrderDetail = $orderDetail;
                    }
                } else {
                    $minCostOrderDetail = $orderDetail;
                }
            }
        }

        return $minCostOrderDetail;
    }

    /**
     * Вычисление корректной суммы расхода
     *
     * @param Collection $collection
     * @param float $revenue
     * @param float $self_value
     * @return array
     * @throws \ChrisKonnertz\StringCalc\Exceptions\ContainerException
     * @throws \ChrisKonnertz\StringCalc\Exceptions\NotFoundException
     */
    protected function getCurrentSum(Collection $collection, float $revenue = 0, float $self_value = 0)
    {
        //Парсим формулу если есть переменная, заменяем
        foreach ($collection as $value) {
            $value->summ = str_replace(self::REVENUE, $revenue, $value->summ);
            $value->summ = str_replace(self::SELF_VALUE, $self_value, $value->summ);
        }
        $expenses = [];
        /**
         * @var $value ExpenseSettings
         */
        foreach ($collection as $value) {
            if($this->cmpCategories && $value->expenseCategory) {
                $name = $value->expenseCategory->name;
            } else {
                $name = $value->name;
            }
            $expenses[] = ['name' => $name, 'summ' => round($this->calc->calculate($value->summ))];
        }

        $expenses = self::sumDuplicated($expenses);

        return $expenses;
    }

    /**
     * Складываем расходы с одинаковыми именами
     *
     * @param array $arr
     * @return array
     */
    public static function sumDuplicated(array $arr)
    {
        //расходы должны складываться если у них одинаковое название
        //Новый массив
        $deleteDuplicateArr = [];
        //елементы которые нужно удалить
        $deleteEl = [];
        for($i = 0; $i < count($arr); ++$i) {
            if(!empty($deleteDuplicateArr)) {
                for ($j = 0; $j < count($deleteDuplicateArr); ++$j) {
                    if($deleteDuplicateArr[$j]['name'] == $arr[$i]['name']) {
                        if($j != $i) {
                            $deleteDuplicateArr[$j]['summ'] += $arr[$i]['summ'];
                            $deleteEl[] = $i;
                        }
                    } else {
                        $deleteDuplicateArr[$i] = $arr[$i];
                    }
                }
            } else {
                $deleteDuplicateArr[0] = $arr[0];
            }
        }
        foreach ($deleteEl as $value) {
            unset($deleteDuplicateArr[$value]);
        }


        $deleteDuplicateArr = array_values($deleteDuplicateArr);

        return array_values($deleteDuplicateArr);
    }

    /**
     * Высчитываем себестоимость всего заказа
     *
     * @param Order $order
     * @return float|int
     */
    protected function getSelfValueOrder(Order $order)
    {
        $summ = 0;
        foreach ($order->orderDetails as $orderDetail) {
            $summ += $orderDetail->product->getPrice(!$this->todayCourse ? $order->created_at : null);
        }

        return $summ;
    }

    /**
     * Подсчет суммарных кастомных расходов
     *
     * @param array $expenses
     * @return int|mixed
     */
    protected function getExpensesSum(array $expenses)
    {
        $expensSum = 0;
        foreach ($expenses as $expens) {
            $expensSum += $expens['summ'];
        }

        return $expensSum;
    }

    /**
     * Получение и вычисление данных по UTM метке
     *
     * @param $orders
     * @param float $costs
     * @return array
     * @throws \ChrisKonnertz\StringCalc\Exceptions\ContainerException
     * @throws \ChrisKonnertz\StringCalc\Exceptions\NotFoundException
     */
    public function calculateUtm($orders, float $costs)
    {
        $result = [];
        $revenue = 0;
        $allExpenses = [];
        if (is_array($orders)) {
            $orders = array_keys($orders);
            $orders = Order::whereIn('id', $orders)->get();
            /**
             * @var $order Order
             */
            foreach ($orders as $order) {
                $number = $this->checkPhone($order->customer->phone);
                if ($order->customer->phone != '89999999999' &&
                    $order->customer->phone != '79999999999' &&
                    strcasecmp($order->comment, 'test') != 0 &&
                    strcasecmp($order->comment, 'тест') != 0 &&
                    $number && strcasecmp($order->customer->first_name, 'test') != 0 &&
                    strcasecmp($order->customer->first_name, 'тест') != 0) {
                    $orderExpenses = $this->calculateOrder($order);
                    if(!empty($orderExpenses)) {
                        $revenue += $orderExpenses['revenue'];
                        if (is_array($orderExpenses)) {
                            $allExpenses = array_merge($allExpenses, $orderExpenses['expense']);
                        }
                    }
                }
            }
        }
        $expenseSum = $this->getExpensesSum($allExpenses);
        $allExpenses = self::sumDuplicated(array_values($allExpenses));
        $result['expenses'] = $allExpenses;
        $result['profit'] = round($revenue - $costs - $expenseSum);
        $result['expense_sum'] = round($expenseSum);

        return $result;
    }

    /**
     * Проверка на то что номер состоит из одинаковых цифр
     *
     * @param string $number
     * @return bool
     */
    protected function checkPhone(string $number): bool
    {
        for ($i = 0; $i <= 9; ++$i) {
            $checkStr = '';
            $checkStr = str_pad($checkStr, 6, $i);
            if(stristr($number, $checkStr)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Определяем выручку и себестоимость
     *
     * @param Order $order
     * @return float
     */
    protected function whichOrderDetailBought(Order $order) : array
    {
        $revenue = 0;
        $selfValue = 0;
        $orderDetails = $order->orderDetails->filter(
            function (OrderDetail $orderDetail) {
                return in_array($orderDetail->currentState()->id, ($this->successful_states ?? []));
            }
        );
        if ($orderDetails->count() < 1) {
            $orderDetails = $order->orderDetails->filter(
                function (OrderDetail $orderDetail) {
                    return is_array($this->minimal_states) ? in_array(
                            $orderDetail->currentState()->id,
                            $this->minimal_states
                        ) && $orderDetail->product->need_guarantee : false;
                }
            )->sortBy('price')->slice(0, 1);
        }
        if ($orderDetails->count() < 1) {
            $orderDetails = $order->orderDetails->filter(
                function (OrderDetail $orderDetail) {
                    return is_array($this->minimal_states) ? in_array(
                        $orderDetail->currentState()->id,
                        $this->minimal_states
                    ) : false;
                }
            )->sortBy('price')->slice(0, 1);
        }

        foreach ($orderDetails as $orderDetail) {
            $revenue += $orderDetail->price * $orderDetail->currency->currency_rate;
            $selfValue += $orderDetail->product->getPrice(!$this->todayCourse ? $order->created_at : null);
        }

        return ['revenue' => $revenue, 'self_value' => $selfValue];
    }
}