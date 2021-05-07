<?php

namespace App\Services\Telephony\Factories;

use App\Call;
use App\CallEvent;
use App\PhoneEvent;
use App\Services\Telephony\Interfaces\TelephonyFactoryInterface;


/**
 * Class AbstractFactory
 * @package App\Services\Telephony\Factories
 */
abstract class AbstractFactory implements TelephonyFactoryInterface
{

    /**
     * @var
     */
    protected $telephonyName;

    /**
     * @return string
     */
    public function getTelephonyName(): string
    {
        return $this->telephonyName;
    }
}
