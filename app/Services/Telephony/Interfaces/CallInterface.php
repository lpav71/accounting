<?php

namespace App\Services\Telephony\Interfaces;

/**
 * Interface CallInterface
 * @package App\Services\Telephony
 */
interface CallInterface
{
    /**
     * is call answered
     *
     * @return bool
     */
    public function isAnswered(): bool;

    /**
     * is outgoing call
     *
     * @return bool
     */
    public function isOutgoing(): bool;

    /**
     * is call has record
     *
     * @return bool
     */
    public function isHasRecord(): bool;

    /**
     * get recording started App\CallEvent
     *
     * @return mixed
     */
    public function getRecordingStartedEvent();
}
