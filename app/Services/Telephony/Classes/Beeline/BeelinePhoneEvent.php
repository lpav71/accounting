<?php

namespace App\Services\Telephony\Classes\Beeline;

use App\PhoneEvent;
use App\Services\Telephony\Interfaces\PhoneEventInterface;
use App\Services\Telephony\Traits\TelephonyModelTrait;

/**
 * App\Services\Telephony\Classes\Beeline\BeelinePhoneEvent
 *
 * @property int $id
 * @property string $phone
 * @property string $type
 * @property string $request
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $telephony_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelinePhoneEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelinePhoneEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Beeline\BeelinePhoneEvent query()
 * @mixin \Eloquent
 */
class BeelinePhoneEvent extends PhoneEvent implements PhoneEventInterface
{

    use TelephonyModelTrait;

    private static $telephonyName = 'beeline';

    protected $table = 'phone_events';
}
