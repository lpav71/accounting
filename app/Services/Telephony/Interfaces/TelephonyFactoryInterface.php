<?php

namespace App\Services\Telephony\Interfaces;


/**
 * Interface TelephonyFactoryInterface
 * @package App\Services\Telephony
 */
interface TelephonyFactoryInterface
{

    /**
     * returns class name of CallInterface implementation
     *
     * @return string
     */
    public function call(): string;

    /**
     * returns class name of CallEventInterface implementation
     *
     * @return string
     */
    public function callEvent(): string;

    /**
     * returns class name of PhoneEventInterface implementation
     *
     * @return string
     */
    public function phoneEvent(): string;

    /**
     * get new eventRequest
     *
     * @param $request
     * @return EventRequestInterface
     */
    public function eventRequest($request): EventRequestInterface;

    /**
     * get current telephony name
     *
     * @return mixed
     */
    public function getTelephonyName(): string;

    /**
     * return name of SuvscriberInterface
     *
     * @return string
     */
    public function subscriber():string;
}
