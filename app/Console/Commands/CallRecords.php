<?php

namespace App\Console\Commands;

use App\Call;
use App\Services\Telephony\Interfaces\CallInterface;
use App\Services\Telephony\TelephonyFactory;
use Illuminate\Console\Command;
use Orchestra\Parser\Xml\Facade as XmlParser;
use Illuminate\Support\Facades\Storage;
use Curl;

class CallRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'call:records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get call records from beeline telephony';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $telephony = (new TelephonyFactory)->getTelephonyFactory('beeline');
        $telephony->call()::whereNull('is_recordable')->get()->each(function (CallInterface $call) {
            $call->update([
                'is_recordable' => (int)($call->isHasRecord() || $call->isAnswered()),
            ]);
        });

        $telephony->call()::where('is_recordable', 1)->whereNull('recordUrl')->get()->each(
            function (CallInterface $call) {

                $token = config('telephony.beeline_token');

                if ($call->number_attempt_request_record > 10) {
                    $call->update([
                        'recordUrl' => '',
                    ]);
                } elseif ($call->isHasRecord()) {
                    $xml = XmlParser::extract(str_replace("xsi1:", "",
                        str_replace("xsi:", "", $call->getRecordingStartedEvent()->request)));
                    $event = $xml->parse(
                        [
                            'extTrackingId' => ['uses' => 'eventData.call.extTrackingId'],
                            'addressOfRecord' => ['uses' => 'eventData.call.endpoint.addressOfRecord'],
                        ]
                    );

                    if (isset($event['extTrackingId']) && isset($event['addressOfRecord'])) {
                        $response = Curl::to(
                            'https://cloudpbx.beeline.ru/apis/portal/records/'
                            .$event['extTrackingId'].'/'
                            .substr($event['addressOfRecord'], 1)
                            .'/reference'
                        )
                            ->withHeader('X-MPBX-API-AUTH-TOKEN: '.$token)
                            ->asJson(true)
                            ->get();
                        if (isset($response['url'])) {
                            $call->update([
                                'recordUrl' => "calls/{$call->id}/{$call->id}.mp3",
                            ]);
                            Storage::disk('local')->makeDirectory("calls/{$call->id}/");
                            Curl::to($response['url'])->download(Storage::disk('local')->path("calls/{$call->id}/{$call->id}.mp3"));
                            $call->callLength();
                        }
                    }
                } else {
                    $response = Curl::to('https://cloudpbx.beeline.ru/apis/portal/abonents')
                        ->withHeader('X-MPBX-API-AUTH-TOKEN: '.$token)
                        ->asJson(true)
                        ->get();
                    if (is_array($response)) {
                        foreach ($response as $abonent) {
                            $abonentResponse = Curl::to('https://cloudpbx.beeline.ru/apis/portal/records/'.$call->extTrackingId.'/'.$abonent['userId'].'/reference')
                                ->withHeader('X-MPBX-API-AUTH-TOKEN: '.$token)
                                ->asJson(true)
                                ->get();
                            if (isset($abonentResponse['url'])) {
                                $call->update([
                                    'recordUrl' => "calls/{$call->id}/{$call->id}.mp3",
                                ]);
                                Storage::disk('local')->makeDirectory("calls/{$call->id}/");
                                Curl::to($abonentResponse['url'])->download(Storage::disk('local')->path("calls/{$call->id}/{$call->id}.mp3"));
                                $call->callLength();
                                break;
                            }
                        }
                    }
                }

                $call->update([
                    'number_attempt_request_record' => (int)$call->number_attempt_request_record + 1,
                ]);
            }
        );
    }
}
