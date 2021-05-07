<?php

namespace App\Services\Telephony\Factories;

use App\PhoneEvent;
use App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCall;
use App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCallEvent;
use App\Services\Telephony\Classes\Matrixmobile\MatrixmobileEventRequest;
use App\Services\Telephony\Classes\Matrixmobile\MatrixmobilePhoneEvent;
use App\Services\Telephony\Classes\Matrixmobile\MatrixmobileSubscriber;
use App\Services\Telephony\Interfaces\EventRequestInterface;

class MatrixmobileFactory extends AbstractFactory
{


    /**
     * returns class name of CallInterface implementation
     *
     * @return string
     */
    public function call(): string
    {
        return MatrixmobileCall::class;
    }

    /**
     * returns class name of CallEventInterface implementation
     *
     * @return string
     */
    public function callEvent(): string
    {
        return MatrixmobileCallEvent::class;
    }

    /**
     * returns class name of PhoneEventInterface implementation
     *
     * @return string
     */
    public function phoneEvent(): string
    {
        return MatrixmobilePhoneEvent::class;
    }

    /**
     * get new eventRequest
     *
     * @param $request
     * @return EventRequestInterface
     */
    public function eventRequest($request): EventRequestInterface
    {
        return new MatrixmobileEventRequest($request);
    }

    /**
     * @inheritDoc
     */
    public function subscriber(): string
    {
        return MatrixmobileSubscriber::class;
    }
}
