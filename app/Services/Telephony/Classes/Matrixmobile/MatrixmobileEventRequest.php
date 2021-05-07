<?php

namespace App\Services\Telephony\Classes\Matrixmobile;

use App\Services\Telephony\Classes\Abstracted\AbstractEventRequest;
use App\Services\Telephony\Interfaces\EventRequestInterface;
use Illuminate\Http\Request;

class MatrixmobileEventRequest extends AbstractEventRequest
{

    /**
     * @var array
     */
    private $event;

    /**
     * @var string
     */
    private static $telephonyName = 'beeline';


    public function __construct(Request $request)
    {
        $this->event = $request->input();
        parent::__construct($request);
    }
    /**
     * is request consists call event
     *
     * @return bool
     */
    public function isCallExists(): bool
    {
        return isset($this->event['callid']);
    }

    /**
     * Used to filter subscriptions owned by the given subscriber.
     *
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return '';
    }

    /**
     * Used to filter subscriptions against the given target (user or collection of users).
     *
     * @return string
     */
    public function getTargetId(): string
    {
        return '';
    }

    /**
     * type of event
     *
     * @return string
     * @throws \Exception
     */
    public function getType(): string
    {

        switch ($this->event['cmd']) {
            case 'history':
                return $this->event['status'];
                break;
            case 'event':
                return $this->event['type'];
                break;
            case 'contact':
                return 'contact';
                break;
            default:
                return '';
        }
    }

    /**
     * customer's phone
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->event['phone'];
    }

    /**
     *
     * call id in external database
     * @return string
     */
    public function getExternalTracking(): string
    {
        return $this->event['callid'];
    }

    /**
     * is this call released event
     *
     * @return bool
     */
    public function isCallReleased(): bool
    {
        return $this->event['type'] == 'COMPLETED';
    }

    /**
     * is this call lost
     *
     * @return bool
     */
    public function isLostCall(): bool
    {
        if ($this->event['cmd'] == 'history') {
            if ($this->event['status'] == 'missed') {
                return true;
            }
        }
        return false;
    }

    /**
     * is request consist link to record
     *
     * @return bool
     * @throws \Exception
     */
    public function hasRecordUrl(): bool
    {
        return $this->getType() == 'Success';
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getRecordUrl(): string
    {
        if ($this->hasRecordUrl()) {
            return $this->event['link'];
        }

        return '';
    }

    /**
     * get telephony number that receives call
     *
     * @return string
     */
    public function getTelephonyNumber(): string
    {
        return isset($this->event['diversion']) ? $this->event['diversion'] : '';
    }
}
