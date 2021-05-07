<?php

namespace App\Http\Controllers;

use App\CallEvent;
use App\Channel;
use App\Services\Telephony\TelephonyFactory;
use Carbon\Carbon;
use Curl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Log;
use Orchestra\Parser\Xml\Facade as XmlParser;
use App\PhoneEvent;
use App\Call;
use App\Customer;
use App\Order;
use DB;
use App\OrderState;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhoneController extends Controller
{
    /**
     * PhoneController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:order-edit', ['except' => ['event']]);
        $this->middleware('permission:phone-calls-list', ['only' => ['calls']]);
    }

    public function record(Call $call)
    {
        $response = new BinaryFileResponse(Storage::disk('local')->path('calls/'.$call->id.'/'.$call->id.'.mp3'));
        BinaryFileResponse::trustXSendfileTypeHeader();

        return $response;
    }

    public function event(Request $request)
    {
        Log::channel('telephony_webhook_log')->info($request->all());
        //creation of the telephony from which the request comes
        $telephony = (new TelephonyFactory())->getTelephonyFactory('beeline');
        if (isset($request->crm_token) && 'matrixmobile_key' == $request->crm_token) {
            $telephony = (new TelephonyFactory())->getTelephonyFactory('matrixmobile');
        }

        //parse request sended from api
        $eventRequest = $telephony->eventRequest($request);
        /**
         * @var $call Call
         */
        //creation of Call if the call exists
        if ($eventRequest->isCallExists()) {
            $call = ($telephony->call())::firstOrNew(['extTrackingId' => $eventRequest->getExternalTracking()]);
            $call->save();
            //saving of a record if it consists in the request
            if ($eventRequest->hasRecordUrl() && !empty($eventRequest->getRecordUrl())) {
                $call->update([
                    'recordUrl' => "calls/{$call->id}/{$call->id}.mp3",
                ]);
                Storage::disk('local')->makeDirectory("calls/{$call->id}/");
                Curl::to($eventRequest->getRecordUrl())->download(Storage::disk('local')->path("calls/{$call->id}/{$call->id}.mp3"));
                $call->callLength();
            }
            $telephonyCallEvent = $telephony->callEvent();
            //creation of the CallEvent
            (new $telephonyCallEvent)->fill(
                [
                    'call_id' => $call->id,
                    'phone' => empty($eventRequest->getPhone()) ? null : $eventRequest->getPhone(),
                    'request' => $eventRequest->getRequest(),
                    'subscriptionId' => empty($eventRequest->getSubscriptionId()) ? null : $eventRequest->getSubscriptionId(),
                    'targetId' => empty($eventRequest->getTargetId()) ? null : $eventRequest->getType(),
                    'type' => $eventRequest->getType(),
                ]
            )->save();
            //creation of a new order if there is a lost call
            if ($eventRequest->isLostCall() && strlen($eventRequest->getPhone()) >= 10 && strlen($eventRequest->getTelephonyNumber()) >= 10) {
                $channel = Channel::where('telephony_numbers', 'LIKE', '%' . $eventRequest->getTelephonyNumber() . '%')->first();
                if ($channel) {
                    $formattedPhone = ($eventRequest->getPhone() ? preg_replace(
                        '/[\s\+\(\)\-]/',
                        '',
                        $eventRequest->getPhone()
                    ) : null);
                    /**
                     * @var $customer Customer
                     */
                        $customer = Customer::where('phone', $formattedPhone)->first();
                        if(!isset($customer)){
                            $customer =  new Customer([
                                'first_name' => __('Anonymous'),
                                'phone' => $formattedPhone,
                            ]);
                            $customer->save();
                        }
                    $addingOrder = $customer->orders()->where('channel_id', $channel->id)->where(
                        'is_new_api_order',
                        1
                    )->latest()->first();
                    if ((bool) $addingOrder) {
                        $order = $addingOrder;
                        $orderComment = $addingOrder->comment . '; ' . Carbon::now()->format('H:i:s') . ' ' . __(
                            'Lost Call'
                        );
                        $order->update(
                            [
                                'comment' => $orderComment,
                            ]
                        );
                    } else {
                        $order = Order::create(
                            [
                                'customer_id' => $customer->id,
                                'channel_id' => $channel->id,
                                'comment' => __('Lost Call'),
                                'is_new_api_order' => 1,
                            ]
                        );
                        $order->states()->save(OrderState::where('name', 'Новый')->firstOrFail());
                    }
                }
            }
        }
        //cretion of a new PhoneEvent
        $telephonyPhoneEvent = $telephony->phoneEvent();
        (new $telephonyPhoneEvent)->fill(
            [
                'type' => $eventRequest->getType(),
                'phone' => $eventRequest->getPhone(),
                'request' => $eventRequest->getRequest(),
            ]
        )->save();
        //deleting Call duplicates
        Call::all()->groupBy('extTrackingId')->filter(function (Collection $items) {
            return $items->count() > 1;
        })->each(function (Collection $calls) {
            /**
             * @var \App\Call $firstCall
             */
            $firstCall = $calls->shift();

            $calls->each(function (Call $call) use ($firstCall) {
                CallEvent::whereCallId($call->id)->update(['call_id' => $firstCall->id]);
                $call->delete();
            });
        });
    }


    public function calls(Request $request)
    {
        $telephonyUsers = [];
        $callLength = 0;

        $client = new \GuzzleHttp\Client([
            'headers' => ['X-MPBX-API-AUTH-TOKEN' => config('telephony.beeline_token')],
        ]);
        $abonents = $client->request('GET', 'https://cloudpbx.beeline.ru/apis/portal/abonents');
        $abonents = json_decode($abonents->getBody()->getContents());
        $abonentsStats = [];

        foreach ($abonents as $abonent) {
            $telephonyUsers[$abonent->userId] = $abonent->userId;

            $callsCollect = collect();
            $lastCallId = 0;
            do {
                $query = '&userId=' . $abonent->userId;
                $query .= '&id=' . $lastCallId;
                if (isset($request->date_from)) {
                    $query .= '&dateFrom=' . $request->date_from.'T00%3A00%3A00.000Z';
                }
                if (isset($request->date_to)) {
                    $query .= '&dateTo=' . $request->date_to.'T23%3A59%3A59.000Z';
                }
                $calls = $client->request(
                    'GET',
                    'https://cloudpbx.beeline.ru/apis/portal/records?' . $query
                );
                $calls = json_decode($calls->getBody()->getContents());
                if (count($calls) == 0) {
                    break;
                }
                $callsCollect = $callsCollect->merge(collect($calls));
                $callsCollect = $callsCollect->sortBy('date');
                $lastCallId = $callsCollect->pop()->id;
            } while (count($calls) >= 100);

            $abonentsStats[$abonent->userId]['duration'] =  round($callsCollect->sum('duration') / 60000);
            $abonentsStats[$abonent->userId]['count'] = $callsCollect->count();
        }



        return view('phone-calls.list', compact('callLength', 'abonentsStats'));
    }
}
