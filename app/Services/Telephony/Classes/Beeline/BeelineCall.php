<?php

namespace App\Services\Telephony\Classes\Beeline;

use App\Call;
use App\CallEvent;
use App\Services\Telephony\Interfaces\CallInterface;
use App\Services\Telephony\Traits\TelephonyModelTrait;

/**
 * Class BeelineCall
 *
 * @package App\Services\Telephony
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelineCall newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelineCall newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelineCall query()
 * @mixin \Eloquent
 */
class BeelineCall extends Call implements CallInterface
{

    use TelephonyModelTrait;

    protected $table = 'calls';

    private static $telephonyName = 'beeline';


    /**
     * @return bool
     */
    public function isOutgoing(): bool
    {
        return (bool) $this->events->where('type', 'CallOriginatedEvent')->filter(function (CallEvent $event) {
            return strlen($event->phone) >= 10;
        })->first();
    }

    /**
     * @return bool
     */
    public function isHasRecord(): bool
    {
        return (bool) $this->events->where('type', 'CallRecordingStartedEvent')->first();
    }

    /**
     * @return bool
     */
    public function isAnswered(): bool
    {
        return (bool) $this->getRecordingStartedEvent();
    }

    /**
     * @return mixed
     */
    public function getRecordingStartedEvent()
    {
        return $this->events->where('type', 'CallRecordingStartedEvent')->first();
    }
}
