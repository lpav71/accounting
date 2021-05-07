<?php

namespace App\Services\Telephony\Classes\Matrixmobile;

use App\PhoneEvent;
use App\Services\Telephony\Interfaces\PhoneEventInterface;
use App\Services\Telephony\Traits\TelephonyModelTrait;

/**
 * App\Services\Telephony\Classes\Matrixmobile\MatrixmobilePhoneEvent
 *
 * @property int $id
 * @property string $phone
 * @property string $type
 * @property string $request
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $telephony_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobilePhoneEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobilePhoneEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobilePhoneEvent query()
 * @mixin \Eloquent
 */
class MatrixmobilePhoneEvent extends PhoneEvent implements PhoneEventInterface
{


    use TelephonyModelTrait;

    protected $table = 'phone_events';

    private static $telephonyName = 'matrixmobile';
}
