<?php

namespace App\Services\Telephony\Classes\Matrixmobile;

use App\Call;
use App\CallEvent;
use App\Services\Telephony\Interfaces\CallInterface;
use App\Services\Telephony\Traits\TelephonyModelTrait;

/**
 * App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCall
 *
 * @property int $id
 * @property string $extTrackingId
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $recordUrl
 * @property int $number_attempt_request_record
 * @property int|null $is_recordable
 * @property int|null $length
 * @property string $telephony_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CallEvent[] $events
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCall newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCall newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCall query()
 * @mixin \Eloquent
 */
class MatrixmobileCall extends Call implements CallInterface
{


    use TelephonyModelTrait;

    protected $table = 'calls';

    private static $telephonyName = 'matrixmobile';

    /**
     * is call answered
     *
     * @return bool
     */
    public function isAnswered(): bool
    {
        return (bool) $this->events->where('type', 'ACCEPTED')->first();
    }

    /**
     * is outgoing call
     *
     * @return bool
     */
    public function isOutgoing(): bool
    {
        return (bool) $this->events->where('type', 'OUTGOING')->filter(function (CallEvent $event) {
            return strlen($event->phone) >= 10;
        })->first();
    }

    /**
     * is call has record
     *
     * @return bool
     */
    public function isHasRecord(): bool
    {
        return (bool) $this->events->where('type', 'Success')->first();
    }

    /**
     * get recording started App\CallEvent
     *
     * @return mixed
     */
    public function getRecordingStartedEvent()
    {
        return $this->events->where('type', 'ACCEPTED')->first();
    }
}
