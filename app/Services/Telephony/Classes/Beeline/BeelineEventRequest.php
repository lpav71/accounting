<?php

namespace App\Services\Telephony\Classes\Beeline;

use App\Services\Telephony\Classes\Abstracted\AbstractEventRequest;
use App\Services\Telephony\TelephonyFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use XmlParser;

/**
 * Class BeelineEventRequest
 * @package App\Services\Telephony\Classes\Beeline
 */
class BeelineEventRequest extends AbstractEventRequest
{

    /**
     * @var array
     */
    private $event;

    /**
     * @var string
     */
    private static $telephonyName = 'beeline';

    /**
     * BeelineEventRequest constructor.
     * @param $request
     */
    public function __construct(Request $request)
    {
        $xml = XmlParser::extract(str_replace("xsi1:", "", str_replace("xsi:", "", $request->getContent())));
        $this->event = $xml->parse(
            [
                'subscriptionId' => ['uses' => 'subscriptionId'],
                'targetId' => ['uses' => 'targetId'],
                'type' => ['uses' => 'eventData::type'],
                'extTrackingId' => ['uses' => 'eventData.call.extTrackingId'],
                'phone' => ['uses' => 'eventData.call.remoteParty.address'],
                'personality' => ['uses' => 'eventData.call.personality'],
            ]
        );
        parent::__construct($request);
    }

    /**
     * @return bool
     */
    public function isCallExists(): bool
    {
        return isset($this->event['extTrackingId']);
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return $this->event['subscriptionId'] ?: '';
    }

    /**
     * @return string
     */
    public function getTargetId(): string
    {
        return $this->event['targetId'] ?: '';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->event['type'] ?: '';
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->event['phone'] ? str_replace('tel:', '', $this->event['phone']) : '';
    }

    /**
     * @return string
     */
    public function getExternalTracking(): string
    {
        return $this->event['extTrackingId'] ?: '';
    }

    /**
     * @return bool
     */
    public function isCallReleased(): bool
    {
        return $this->event['type'] == 'CallReleasedEvent';
    }

    /**
     * is this call lost
     *
     * @return bool
     * @throws \Exception
     */
    public function isLostCall(): bool
    {
        $telephony = (new TelephonyFactory())->getTelephonyFactory(self::$telephonyName);
        try {
            $call = ($telephony->call())::where(['extTrackingId' => $this->getExternalTracking()])->firstOrFail();
        } catch (\Exception $e) {
            return true;
        }
        if(!$call->isAnswered() && !$call->isOutgoing() && $this->isCallReleased()){
            sleep(120);
        }
        try {
            $call = ($telephony->call())::where(['extTrackingId' => $this->getExternalTracking()])->firstOrFail();
        } catch (\Exception $e) {
            return true;
        }
        return !$call->isAnswered() && !$call->isOutgoing() && $this->isCallReleased();
    }

    /**
     * is request consist link to record
     *
     * @return bool
     */
    public function hasRecordUrl(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getRecordUrl(): string
    {
        return '';
    }

    /**
     * get telephony number that receives call
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTelephonyNumber(): string
    {
        $client = new Client();
        try {
            $res = $client->request(
                'GET',
                'https://cloudpbx.beeline.ru/apis/portal/abonents/' . $this->getTargetId(),
                [
                    'headers' => ['X-MPBX-API-AUTH-TOKEN' => config('telephony.beeline_token')],
                ]
            );
            $phone = json_decode($res->getBody()->getContents())->phone;
            return $phone;
        } catch (\Exception $e) {
            return '';
        }
    }
}
