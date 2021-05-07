<?php

namespace App\Services\Telephony\Classes\Matrixmobile;

use App\CallEvent;
use App\Services\Telephony\Interfaces\CallEventInterface;
use App\Services\Telephony\Traits\TelephonyModelTrait;

/**
 * App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCallEvent
 *
 * @property int $id
 * @property int $call_id
 * @property string|null $phone
 * @property string $type
 * @property string $request
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $targetId
 * @property string|null $subscriptionId
 * @property string $telephony_name
 * @property-read \App\Call $call
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCallEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCallEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Telephony\Classes\Matrixmobile\MatrixmobileCallEvent query()
 * @mixin \Eloquent
 */
class MatrixmobileCallEvent extends CallEvent implements CallEventInterface
{


    use TelephonyModelTrait;

    protected $table = 'call_events';

    private static $telephonyName = 'matrixmobile';
}
