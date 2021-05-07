<?php


namespace App\Services\Telephony\Classes\Matrixmobile;

use App\Services\Telephony\Interfaces\SubscriberInterface;
use App\Services\Telephony\Traits\TelephonyModelTrait;
use App\TelephonyAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Services\Telephony\Classes\Matrixmobile\MatrixmobileSubscriber
 *
 * @property int $id
 * @property string $name
 * @property string $login
 * @property string $telephony_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileSubscriber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileSubscriber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileSubscriber query()
 * @mixin \Eloquent
 */
class MatrixmobileSubscriber extends TelephonyAccount implements SubscriberInterface
{

    use TelephonyModelTrait;

    /**
     * @var string
     */
    protected $table = 'telephony_accounts';

    /**
     * @var string
     */
    private static $telephonyName = 'matrixmobile';

    /**
     * @inheritDoc
     */
    public function getCallsApi(Carbon $timeFrom, Carbon $timeTo): Collection
    {
        $calls = MatrixmobileCallEvent::whereBetween('created_at', [$timeFrom, $timeTo])->where('type', 'Success')->get();
        $calls = $calls->filter(function (MatrixmobileCallEvent $item) {
            parse_str($item->request, $output);
            if ($output['user'] == $this->login) {
                return true;
            }
            return false;
        });
        return $calls;
    }

    /**
     * @inheritDoc
     */
    public function getCallLengthApi(Carbon $timeFrom, Carbon $timeTo): int
    {
        return $this->getCallsApi($timeFrom, $timeTo)->map(function (MatrixmobileCallEvent $item) {
            parse_str($item->request, $output);
            return $output;
        })->sum('duration');
    }

    /**
     * @inheritDoc
     */
    public function getCallCountApi(Carbon $timeFrom, Carbon $timeTo): int
    {
        return $this->getCallsApi($timeFrom, $timeTo)->map(function (MatrixmobileCallEvent $item) {
            parse_str($item->request, $output);
            return $output;
        })->count();
    }

}