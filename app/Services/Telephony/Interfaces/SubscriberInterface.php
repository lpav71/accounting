<?php


namespace App\Services\Telephony\Interfaces;


use App\TelephonyAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface SubscriberInterface
{

    /**
     * get all calls from time $timeFrom to time $timeTo
     *
     * @param Carbon $timeFrom
     * @param Carbon $timeTo
     * @return Collection
     */
    public function getCallsApi(Carbon $timeFrom, Carbon $timeTo): Collection;

    /**
     * get call length in seconds
     *
     * @param Carbon $timeFrom
     * @param Carbon $timeTo
     * @return int
     */
    public function getCallLengthApi(Carbon $timeFrom, Carbon $timeTo): int;

    /**
     * @param Carbon $timeFrom
     * @param Carbon $timeTo
     * @return int
     */
    public function getCallCountApi(Carbon $timeFrom, Carbon $timeTo): int;

}