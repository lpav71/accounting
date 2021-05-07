<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Certificate
 * @package App
 * @property string $number
 * @property int $order_detail_id
 */
class Certificate extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'number',
        'order_detail_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function operations()
    {
        return $this->hasMany(Operation::class);
    }

    /**
     * Заказы где применяются сертификаты
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class);
    }

    /**
     * Получение начально кол-во валюты сертификата
     *
     * @return float
     */
    public function getSum(): int
    {
        return $this->getDebitCertificateQuantity();
    }

    /**
     * Получение текущего баланса сертификата
     *
     * @return int
     */
    public function getBalance(): int
    {
        $currentDebitQuantity = $this->getDebitCertificateQuantity();
        $currentCreditQuantity = $this->getCreditCertificateQuantity();

        return ($currentDebitQuantity - $currentCreditQuantity);
    }

    /**
     * Кол-во валюты которое было списано с сертификата
     *
     * @return int|mixed
     */
    private function getCreditCertificateQuantity(): int
    {
        return Operation::
        where('certificate_id', $this->id)
            ->where('type', 'C')
            ->sum('quantity');
    }

    /**
     * Кол-во валюты которое было внесено в сертификат
     *
     * @return int|mixed
     */
    private function getDebitCertificateQuantity(): int
    {
        return Operation::
        where('certificate_id', $this->id)
            ->where('type', 'D')
            ->sum('quantity');
    }

    /**
     * Списание средств сертификата по заказу
     *
     * @param int $money
     * @param Order $order
     * @return void
     */
    public function writingOffMoneyByOrder(int $money, Order $order): void
    {
        Operation::create(
            [
                'type' => 'C',
                'quantity' => $money,
                'operable_type' => Currency::class,
                'operable_id' => Currency::first()->id,
                'storage_type' => Cashbox::class,
                'storage_id' => Cashbox::where(['for_certificates' => 1])->first()->id,
                'user_id' => \Auth::id(),
                'certificate_id' => $this->id,
                'comment' => __('Withdrawing funds by order - :order_id', ['order_id' => $order->getDisplayNumber()]),
            ]
        )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);
    }

    /**
     * Списание средств при возврате заказа
     */
    public function writingOffMoney(): void
    {
        Operation::create(
            [
                'type' => 'C',
                'quantity' => $this->getBalance(),
                'operable_type' => Currency::class,
                'operable_id' => Currency::first()->id,
                'storage_type' => Cashbox::class,
                'storage_id' => Cashbox::where(['for_certificates' => 1])->first()->id,
                'user_id' => \Auth::id(),
                'certificate_id' => $this->id,
                'comment' => __('Write-off due to the return of the certificate. Order id - :order_id', ['order_id' => $this->orderDetail->order->getDisplayNumber()]),
            ]
        )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);
    }

    /**
     * Зачисление средств на сертификат
     */
    public function creditingMoney(): void
    {
        Operation::create(
            [
                'type' => 'D',
                'quantity' => $this->orderDetail->price,
                'operable_type' => Currency::class,
                'operable_id' => $this->orderDetail->currency_id,
                'storage_type' => Cashbox::class,
                'storage_id' => Cashbox::where(['for_certificates' => 1])->first()->id,
                'user_id' => \Auth::id(),
                'certificate_id' => $this->orderDetail->certificate->id,
                'comment' => __('Buying certificate'),
            ]
        )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);
    }

    /**
     * Удаление сертификата посредством обнуления его номера
     */
    public function softDelete()
    {
        $this->update(['number' => null]);
    }
}
