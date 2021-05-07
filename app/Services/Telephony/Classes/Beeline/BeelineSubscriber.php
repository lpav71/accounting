<?php


namespace App\Services\Telephony\Classes\Beeline;


use App\Services\Telephony\Traits\TelephonyModelTrait;
use App\TelephonyAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use \App\Services\Telephony\Interfaces\SubscriberInterface;
use Ixudra\Curl\Facades\Curl;


/**
 * App\Services\Telephony\Classes\Beeline\BeelineSubscriber
 *
 * @property int $id
 * @property string $name
 * @property string $login
 * @property string $telephony_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelineSubscriber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelineSubscriber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelineSubscriber query()
 * @mixin \Eloquent
 */
class BeelineSubscriber extends TelephonyAccount implements SubscriberInterface
{

    use TelephonyModelTrait;

    /**
     * @var string
     */
    private static $telephonyName = 'beeline';

    /**
     * @var string
     */
    protected $table = 'telephony_accounts';

    /**
     * @var Collection|null
     */
    private $cachedCalls = null;

    /**
     * @var Carbon|null
     */
    private $cachedTimeFrom = null;

    /**
     * @var Carbon|null
     */
    private $cachedTimeTo = null;

    /**
     * @inheritDoc
     */
    public function getCallsApi(Carbon $timeFrom, Carbon $timeTo): Collection
    {
        if(is_null($this->cachedCalls) || $this->cachedTimeFrom->notEqualTo($timeFrom) ||  $this->cachedTimeTo->notEqualTo($timeTo)){
            $this->cachedCalls = $this->getCallsRequest($timeFrom,$timeTo);
            $this->cachedTimeFrom = $timeFrom;
            $this->cachedTimeTo = $timeTo;
        }
        return $this->cachedCalls;
    }

    /**
     * @inheritDoc
     */
    public function getCallLengthApi(Carbon $timeFrom, Carbon $timeTo): int
    {
       return (int) $this->getCallsApi($timeFrom,$timeTo)->sum('duration') / 1000;
    }

    /**
     * @inheritDoc
     */
    public function getCallCountApi(Carbon $timeFrom, Carbon $timeTo): int
    {
        return $this->getCallsApi($timeFrom,$timeTo)->count();
    }

    /**
     *
     * @param Carbon $timeFrom
     * @param Carbon $timeTo
     * @return Collection
     */
    public function getCallsRequest(Carbon $timeFrom, Carbon $timeTo): Collection
    {
        return collect($this->getCalls($timeFrom, $timeTo));
    }

    /**
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param int $id Начальный ID записи
     * @return array
     */
    private function getCalls(Carbon $dateFrom, Carbon $dateTo, int $id = 0): array
    {
        if ($id == 0) {
            $id = $this->getFirstCallId($dateFrom, $dateTo);
        }
        $dateFromString = $dateFrom->setTimezone('UTC')->format('Y-m-d\TH%3i%3s.u\Z');
        $dateToString = $dateTo->setTimezone('UTC')->format('Y-m-d\TH%3i%3s.u\Z');
        $token = config('telephony.beeline_token');
        $calls = Curl::to('https://cloudpbx.beeline.ru/apis/portal/records?dateFrom=' . $dateFromString .
            '&dateTo=' . $dateToString .
            '&id=' . (int)($id - 1) .
            '&userId='. $this->login)
            ->withHeader('X-MPBX-API-AUTH-TOKEN: ' . $token)
            ->asJson(true)
            ->get();
        if (count($calls) == 100) {
            return array_merge($calls, $this->getCalls($dateFrom, $dateTo, $calls[99]['id']));
        }
        return $calls;
    }

    /**
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return int
     */
    private function getFirstCallId(Carbon $dateFrom, Carbon $dateTo): int
    {
        $dateFromString = $dateFrom->setTimezone('UTC')->format('Y-m-d\TH%3i%3s.u\Z');
        $dateToString = $dateTo->setTimezone('UTC')->format('Y-m-d\TH%3i%3s.u\Z');
        $token = config('telephony.beeline_token');
        $calls = Curl::to('https://cloudpbx.beeline.ru/apis/portal/records?dateFrom=' . $dateFromString .
            '&dateTo=' . $dateToString.
            '&userId='. $this->login)
            ->withHeader('X-MPBX-API-AUTH-TOKEN: ' . $token)
            ->asJson(true)
            ->get();
        if (count($calls) == 0) {
            return 1;
        } else {
            return $calls[0]['id'];
        }
    }
}