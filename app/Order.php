<?php

namespace App;

use App\Traits\HasRandomId;
use App\Traits\ModelLogger;
use App\Traits\OperableVirtualMultipleStorage;
use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kyslik\LaravelFilterable\Filterable;
use Kyslik\ColumnSortable\Sortable;

/**
 * Заказ
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 * @property int $id
 * @property int $order_number
 * @property int $customer_id
 * @property string|null $clientID
 * @property string|null $gaClientID
 * @property int|null $utm_id
 * @property string|null $search_query
 * @property string|null $delivery_post_index
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $date_estimated_delivery
 * @property string|null $delivery_city
 * @property string|null $delivery_address
 * @property string|null $delivery_address_flat
 * @property string|null $delivery_address_comment
 * @property string $carrier_id
 * @property string $user_id
 * @property int $channel_id
 * @property string|null $delivery_shipping_number
 * @property string|null $delivery_start_time
 * @property string|null $delivery_end_time
 * @property string|null $comment
 * @property string|null $pickup_point_code
 * @property string|null $pickup_point_name
 * @property string|null $pickup_point_address
 * @property bool $is_hidden
 * @property int $is_new_api_order
 * @property string|null $device
 * @property string|null $age
 * @property string|null $gender
 * @property-read \App\Carrier|null $carrier
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CdekState[] $cdekStates
 * @property-read \App\Channel $channel
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderComment[] $comments
 * @property-read \App\Customer $customer
 * @property-read string|null $utm_campaign
 * @property-read string|null $utm_source
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetail[] $orderDetails
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductExchange[] $productExchanges
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductReturn[] $productReturns
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderState[] $states
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Task[] $tasks
 * @property-read \App\User|null $user
 * @property-read \App\Utm|null $utm
 * @method static Builder|\App\Order filter(\Kyslik\LaravelFilterable\FilterContract $filters)
 * @method static Builder|\App\Order newModelQuery()
 * @method static Builder|\App\Order newQuery()
 * @method static Builder|\App\Order query()
 * @method static Builder|\App\Order sortable($defaultParameters = null)
 * @mixin \Eloquent
 */
class Order extends Model
{
    use OperableVirtualMultipleStorage, Filterable, Sortable, HasBelongsToManyEvents, HasRandomId, ModelLogger;

    protected $observables = [
        'belongsToManyAttaching',
        'belongsToManyAttached',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'order_number' => 'integer',
    ];

    protected $fillable = [
        'customer_id',
        'clientID',
        'gaClientID',
        'utm_id',
        'search_query',
        'delivery_post_index',
        'delivery_city',
        'delivery_address',
        'delivery_address_flat',
        'delivery_address_comment',
        'carrier_id',
        'user_id',
        'channel_id',
        'delivery_shipping_number',
        'date_estimated_delivery',
        'delivery_start_time',
        'delivery_end_time',
        'comment',
        'pickup_point_code',
        'pickup_point_name',
        'pickup_point_address',
        'is_hidden',
        'is_new_api_order',
        'device',
        'age',
        'gender',
    ];

    /**
     * Массив случайных ключей для трейта HasRandomId
     *
     * @var array
     */
    protected $randomIds = ['order_number' => 6];

    /**
     * Сортируемые поля
     *
     * @var array
     */
    public $sortable = [
        'id',
        'order_number',
        'created_at',
        'customer_id',
        'delivery_city',
        'delivery_address',
        'carrier_id',
        'user_id',
        'channel_id',
        'delivery_shipping_number',
        'date_estimated_delivery',
        'delivery_start_time',
        'delivery_end_time',
    ];

    protected $appends = [
        'utm_campaign',
        'utm_source',
    ];

    /**
     * Геттер utm_campaign
     *
     * @return string|null
     */
    public function getUtmCampaignAttribute()
    {
        return (is_null($this->utm) ? null : $this->utm->campaign);
    }

    /**
     * Геттер utm_source
     *
     * @return string|null
     */
    public function getUtmSourceAttribute()
    {
        return (is_null($this->utm) ? null : $this->utm->source);
    }

    /**
     * Позиции заказа
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    /**
     * Клиент
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Задачи
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Сертификаты заказов
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function certificates()
    {
        return $this->belongsToMany(Certificate::class);
    }

    /**
     * Статусы
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function states()
    {
        return $this
            ->belongsToMany(OrderState::class)
            ->withPivot('created_at')
            ->orderBy('order_order_state.id', 'desc');
    }

    /**
     * Текущий статус
     *
     * @return mixed
     */
    public function currentState()
    {
        return $this->states()->first();
    }

    /**
     * Статусы СДЭК
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function cdekStates()
    {
        return $this
            ->belongsToMany(CdekState::class)
            ->withPivot('created_at')
            ->withPivot('task_made')
            ->orderBy('cdek_state_order.created_at', 'desc');
    }

    /**
     * Текущий статус СДЭК
     *
     * @return CdekState
     */
    public function currentCdekState()
    {
        return $this->cdekStates()->first();
    }

    /**
     * Последующие статусы
     *
     * @return \Illuminate\Support\Collection
     */
    public function nextStates()
    {
        return ($this->currentState() ? $this->currentState()->nextStates()->get() : collect([]));
    }

    /**
     * Служба доставка
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Источник
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Исполнитель
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Utm
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function utm()
    {
        return $this->belongsTo(Utm::class);
    }

    /**
     * Возвраты по заказу
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productReturns()
    {
        return $this->hasMany(ProductReturn::class);
    }

    /**
     * Обмены по заказу
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productExchanges()
    {
        return $this->hasMany(ProductExchange::class);
    }

    /**
     * Маршрутные точки
     *
     * @return Collection
     */
    public function routePoints()
    {
        return RoutePoint::where('point_object_type', self::class)
            ->where('point_object_id', $this->id)
            ->get();
    }

    /**
     * Текущий маршрутный лист
     *
     * @return RouteList|null
     */
    public function routeList()
    {
        /**
         * @var RoutePoint $routePoint
         */
        $routePoint = $this->routePoints()->where('is_point_object_attached', 1)->first();

        return (is_null($routePoint) ? null : $routePoint->routeList);
    }

    /**
     * Сеттер date_estimated_delivery
     *
     * @param $value
     */
    public function setDateEstimatedDeliveryAttribute($value)
    {
        $this->attributes['date_estimated_delivery'] = is_null($value) ? null : Carbon::createFromFormat(
            'd-m-Y',
            $value
        )->setTime(0, 0, 0)->format('Y-m-d H:i:s');
    }

    /**
     * Геттер date_estimated_delivery
     *
     * @param $value
     *
     * @return string
     */
    public function getDateEstimatedDeliveryAttribute($value)
    {
        return (is_null($value) ? null : Carbon::createFromFormat('Y-m-d H:i:s', $value)
            ->setTime(0, 0, 0)
            ->format('d-m-Y'));
    }

    /**
     * Сеттер carrier_id
     *
     * @param $value
     */
    public function setCarrierIdAttribute($value)
    {
        $this->attributes['carrier_id'] = ($value == 0 ? null : $value);
    }

    /**
     * Геттер carrier_id
     *
     * @param $value
     *
     * @return string
     */
    public function getCarrierIdAttribute($value)
    {
        return (is_null($value) ? 0 : $value);
    }

    /**
     * Сеттер user_id
     *
     * @param $value
     */
    public function setUserIdAttribute($value)
    {
        $this->attributes['user_id'] = ($value == 0 ? null : $value);
    }

    /**
     * Гетттер user_id
     *
     * @param $value
     *
     * @return string
     */
    public function getUserIdAttribute($value)
    {
        return (is_null($value) ? 0 : $value);
    }

    /**
     * Операции по заказу
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function operations()
    {
        return $this->hasMany(Operation::class);
    }

    /**
     * Открыто ли редактирование товарных позиций в заказе
     *
     * @return bool
     */
    public function isOpenEdit()
    {
        return (bool) !$this->currentState()->is_blocked_edit_order_details;
    }

    /**
     * Получение баланса заказа
     *
     * @param Currency $currency
     *
     * @return mixed
     */
    public function getOrderBalance(Currency $currency)
    {
        return $this->getRealVirtualQuantity($currency->id, Currency::class);
    }

    /**
     * Имеет ли заказ статусы с проверяемой оплатой, в т.ч. исторические
     *
     * @return bool
     */
    public function isCheckedPayment()
    {
        return $this->states()->where('check_payment', '1')->count() > 0;
    }

    /**
     * Получение отформатированного полного адреса доставки
     *
     * @return string
     */
    public function getFullDeliveryAddress()
    {
        return collect(
            [
                'delivery_post_index' => $this->delivery_post_index,
                'delivery_city' => $this->delivery_city,
                'delivery_address' => $this->delivery_address,
                'delivery_address_flat' => $this->delivery_address_flat,
                'delivery_address_comment' => $this->delivery_address_comment,
            ]
        )->filter(
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        )->map(
            function ($item, $key) {
                return ($key == 'delivery_address_flat' ? __('fl').' '.$item : $item);
            }
        )->implode(', ');
    }

    /**
     * Получение отформатированного полного адреса доставки без указания города
     *
     * @return string
     */
    public function getStreetDeliveryAddress()
    {
        return collect(
            [
                'delivery_address' => $this->delivery_address,
                'delivery_address_flat' => $this->delivery_address_flat,
                'delivery_address_comment' => $this->delivery_address_comment,
                'pickup_point_address' => $this->pickup_point_address,
            ]
        )->filter(
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        )->map(
            function ($item, $key) {
                return ($key == 'delivery_address_flat' ? __('fl').' '.$item : ($key == 'pickup_point_address' ? __(
                        'Pickup point'
                    ).': '.$item : $item));
            }
        )->implode(', ');
    }

    /**
     * Получение отформатированного полного адреса доставки для отображения на карте
     *
     * @return string
     */
    public function getMapDeliveryAddress()
    {
        return collect(
            [
                'delivery_post_index' => $this->delivery_post_index,
                'delivery_city' => $this->delivery_city,
                'delivery_address' => $this->delivery_address,
                'delivery_address_flat' => $this->delivery_address_flat,
            ]
        )->filter(
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        )->map(
            function ($item, $key) {
                return ($key == 'delivery_address_flat' ? __('fl').' '.$item : $item);
            }
        )->implode(', ');
    }

    /**
     * Новый заказ через API?
     *
     * @return bool
     */
    public function isNewApiOrder()
    {
        return (bool) $this->is_new_api_order;
    }

    /**
     * Отображаемый номер заказа
     *
     * @return int|string
     */
    public function getDisplayNumber()
    {
        return ($this->id ? self::getDisplayNumberById($this->id) : '');
    }

    /**
     * Внутренний шестизначный номер заказа
     *
     * @return mixed
     */
    public function getOrderNumber()
    {
        return $this->order_number;
    }

    /**
     * Статический метод получения отображаемого номера заказа
     *
     * @param Order $order
     *
     * @return int|string
     */
    public static function getDisplayNumberFromModel(Order $order)
    {
        return $order->getDisplayNumber();
    }

    /**
     * Отображаемый номер заказа по его реальному ID
     *
     * @param $id
     *
     * @return int
     */
    public static function getDisplayNumberById($id)
    {
        return $id + 13550;
    }

    /**
     * Реальный ID по отображаемому номеру заказа
     *
     * @param $id
     *
     * @return int
     */
    public static function getRealNumberById($id)
    {
        return (int) $id - 13550;
    }

    /**
     * Открытые задачи по заказу
     *
     * @return Task[]|Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function openTasks()
    {
        return Task::orWhere('order_id', $this->id)
            ->orWhere('customer_id', $this->customer->id)
            ->get()
            ->filter(
                function (Task $task) {
                    return !$task->isClosed();
                }
            );
    }

    /**
     * Комментарии к заказу
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(OrderComment::class);
    }

    /**
     * Сохранение заказа
     *
     * @param array $options
     *
     * @return bool
     * @todo Содержимое данного метода надо отрефакторить и перенести в другие классы
     */
    public function save(array $options = [])
    {

        if (is_null($this->clientID) && is_null($this->id) && is_null($this->utm_campaign) && is_null(
                $this->search_query
            )) {
            // Если не передан clientID при создании заказа - ищем ближайший заказ от клиента с тем же номером телефона через тот же канал с clientID и присваиваем его
            $orderWithClientId = Order::query()->whereIn(
                'customer_id',
                Customer::query()->where('phone', $this->customer->phone)->pluck('id')
            )
                ->where('channel_id', $this->channel_id)
                ->whereNotNull('clientID')
                ->orderByDesc('id')
                ->first();

            if ($orderWithClientId) {
                $this->clientID = $orderWithClientId->clientID;
                try {
                    $this->utm_id =
                        Utm::firstOrCreate(
                            [
                                'utm_campaign_id' =>
                                    (UtmCampaign::firstOrCreate(['name' => $orderWithClientId->utm_campaign]))->id,
                                'utm_source_id' =>
                                    (UtmSource::firstOrCreate(['name' => $orderWithClientId->utm_source]))->id,
                            ]
                        )->id;
                } catch (\Exception $e) {

                }

                $this->search_query = $orderWithClientId->search_query;
            }

        }

        if (is_null($this->gaClientID) && is_null($this->id) && is_null($this->utm_campaign) && is_null(
                $this->search_query
            )) {
            // Если не передан gaClientID при создании заказа - ищем ближайший заказ от клиента с тем же номером телефона через тот же канал с gaClientID и присваиваем его
            $orderWithClientId = Order::query()->whereIn(
                'customer_id',
                Customer::query()->where('phone', $this->customer->phone)->pluck('id')
            )
                ->where('channel_id', $this->channel_id)
                ->whereNotNull('gaClientID')
                ->orderByDesc('id')
                ->first();

            if ($orderWithClientId) {
                $this->gaClientID = $orderWithClientId->gaClientID;
                if (is_null($this->utm_campaign)) {

                    try {
                        $this->utm_id =
                            Utm::firstOrCreate(
                                [
                                    'utm_campaign_id' =>
                                        (UtmCampaign::firstOrCreate(['name' => $orderWithClientId->utm_campaign]))->id,
                                    'utm_source_id' =>
                                        (UtmSource::firstOrCreate(['name' => $orderWithClientId->utm_source]))->id,
                                ]
                            )->id;
                    } catch (\Exception $e) {

                    }

                    $this->search_query = $orderWithClientId->search_query;
                }
            }

        }

        if (!parent::save($options)) {
            return false;
        }

        return true;
    }

    /**
     * Проверка на возможность быстрого закрытия заказа из редактирования
     *
     * @return bool
     */
    public function canCloseOrder()
    {
        $countSentStates = 0;
        $autoCloseFlag = true;
        /**
         * @var $detail OrderDetail
         */
        foreach ($this->orderDetails()->where('is_exchange', 0)->get() as $detail) {
            if(($detail->currentState()->is_delivered || $detail->currentState()->is_returned || $detail->currentState()->is_sent) && (!$detail->currentState()->is_courier_state)) {
                if($detail->currentState()->is_sent) {
                    $countSentStates++;
                    if($countSentStates > 1) {
                        $autoCloseFlag = false;
                        break;
                    }
                }
                continue;
            } else {
                $autoCloseFlag = false;
                break;
            }
        }

        return $autoCloseFlag;
    }
    /**
     * Получение следующего статуса заказа в зависимости от статусов товаров
     *
     * @return mixed
     */
    public function getNextOrderStateDependingOrderDetailStates()
    {
        $states = [];
        /**
         * @var $orderDetail OrderDetail
         */
        foreach ($this->orderDetails as $orderDetail) {
            if($orderDetail->currentState()->is_delivered) {
                $states[] = 1;
            } else {
                $states[] = 0;
            }
        }

        if(in_array(1, $states)) {
            return $this->nextStates()->where('is_successful', 1)->first();
        }

        return $this->nextStates()->where('is_failure', 1)->first();
    }

    /**
     * Получение всех артиклей по заказу для работы сервиса кликхауса
     *
     * @return array
     */
    public function getAllArticles() : array
    {
        $articles = [];
        foreach ($this->orderDetails as $orderDetail) {
            $articles[] = $orderDetail->product->reference;
        }

        return $articles;
    }

    /**
     * @return Builder
     */
    public static function getNotHidden() : Builder
    {
        return Order::where('is_hidden',0);
    }

    /**
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->currentState()->isFinalState();
    }

    /**
     * order is ready for shipment and not shipped yet
     *
     * @return bool
     */
    public function shipAvailable(): bool
    {
        return $this->currentState()->shipAvailable();
    }

    /**
     * @return bool
     */
    public function active(): bool
    {
        return !$this->currentState()->inactiveOrder();
    }
     /**
     * Статусы трешхолдов
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orderAlerts()
    {
        return $this
            ->belongsToMany(OrderAlert::class, 'order_order_alerts', 'order_id', 'trashold_id' )
            ->orderBy('order_order_alerts.id', 'desc')
            ->withTimestamps();
    }
     
    

    /**
     * Получение баланса всех сертификатов привязанных к заказу
     *
     * @return float
     */
    public function getAllCertificateBalance():float
    {
        $balance = 0;
        if($this->certificates) {
            foreach ($this->certificates as $certificate) {
                $balance += $certificate->getBalance();
            }
        }

        return $balance;
    }

    /**
     * Получение суммы в зависимости от сертификаов заказа
     *
     * @return int
     */
    public function needToPay() :int
    {
        $certificateBalance = $this->getAllCertificateBalance();
        $orderDetailPriceSum = 0;

        foreach ($this->orderDetails as $orderDetail) {
            $orderDetailPriceSum += $orderDetail->price;
        }

        $orderDetailPriceSum -= $this->getCashboxOperationByOrder();

        $needToPay = 0;
        if(($orderDetailPriceSum - $certificateBalance) > 0) {
            $needToPay = $orderDetailPriceSum - $certificateBalance;
        }

        return $needToPay;
    }

    /**
     * Получение кассовых операций связанных с заказом
     *
     * @return int|mixed
     */
    public function getCashboxOperationByOrder() :int
    {
        $currentCreditQuantity = Operation::
            where('type', 'C')
            ->where('operable_type', Currency::class)
            ->where('order_id', $this->id)
            ->sum('quantity');

        $currentDebitQuantity = Operation::
            where('type', 'D')
            ->where('operable_type', Currency::class)
            ->where('order_id', $this->id)
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity);
    }
}
