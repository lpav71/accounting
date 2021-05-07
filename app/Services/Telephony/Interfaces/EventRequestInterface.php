<?php

namespace App\Services\Telephony\Interfaces;


/**
 * parsing request from telephony
 *
 * Interface EventRequestInterface
 * @package App\Services\Telephony\Interfaces
 */
interface EventRequestInterface
{

    /**
     * is request consists call event
     *
     * @return bool
     */
    public function isCallExists(): bool;

    /**
     * Used to filter subscriptions owned by the given subscriber.
     *
     * @return string
     */
    public function getSubscriptionId(): string;

    /**
     * Used to filter subscriptions against the given target (user or collection of users).
     *
     * @return string
     */
    public function getTargetId(): string;

    /**
     * type of event
     *
     * @return string
     */
    public function getType(): string;

    /**
     * customer's phone string / E164
     *
     * @return string
     */
    public function getPhone(): string;

    /**
     *
     * call id in external database
     * @return string
     */
    public function getExternalTracking(): string;

    /**
     * is this call released event
     *
     * @return bool
     */
    public function isCallReleased(): bool;

    /**
     * is this call lost
     *
     * @return bool
     */
    public function isLostCall(): bool;

    /**
     * is request consist link to record
     *
     * @return bool
     */
    public function hasRecordUrl(): bool;

    /**
     * get record url
     *
     * @return string
     */
    public function getRecordUrl(): string;

    /**
     * get telephony number that receives call
     *
     * @return string
     */
    public function getTelephonyNumber(): string;

    /**
     * get request field
     *
     * @return resource|string
     */
    public function getRequest(): string;
}
