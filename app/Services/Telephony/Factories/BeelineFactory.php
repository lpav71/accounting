<?php

namespace App\Services\Telephony\Factories;

use App\Services\Telephony\Classes\Beeline\BeelineCall;
use App\Services\Telephony\Classes\Beeline\BeelineCallEvent;
use App\Services\Telephony\Classes\Beeline\BeelineEventRequest;
use App\Services\Telephony\Classes\Beeline\BeelinePhoneEvent;
use App\Services\Telephony\Classes\Beeline\BeelineSubscriber;
use App\Services\Telephony\Interfaces\EventRequestInterface;

class BeelineFactory extends AbstractFactory
{
    protected $telephonyName = 'beeline';


    /**
     * returns class name of CallInterface implementation
     *
     * @return string
     */
    public function call(): string
    {
        return BeelineCall::class;
    }

    /**
     * returns class name of CallEventInterface implementation
     *
     * @return string
     */
    public function callEvent(): string
    {
        return BeelineCallEvent::class;
    }

    /**
     * returns class name of PhoneEventInterface implementation
     *
     * @return string
     */
    public function phoneEvent(): string
    {
        return BeelinePhoneEvent::class;
    }

    /**
     * get class name of eventRequest
     *
     * @param $request
     * @return EventRequestInterface
     */
    public function eventRequest($request): EventRequestInterface
    {
        return new BeelineEventRequest($request);
    }


    /**
     * @inheritDoc
     */
    public function subscriber(): string
    {
        return BeelineSubscriber::class;
    }
}
