<?php

namespace App\Console\Commands;

use App\Channel;
use App\Configuration;
use Illuminate\Console\Command;
use Curl;

class CallSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'call:subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'An event subscription for telephony';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $token = config('telephony.beeline_token');
        Channel::all()->map(
            function (Channel $channel) use ($token) {
                if ($channel->call_target_id) {
                    $response = [];
                    if ($channel->call_subscription_id) {
                        $response = Curl::to('https://cloudpbx.beeline.ru/apis/portal/subscription')
                            ->withHeader('X-MPBX-API-AUTH-TOKEN: '.$token)
                            ->withData(['subscriptionId' => $channel->call_subscription_id])
                            ->asJson(true)
                            ->get();
                        if (!isset($response['expires']) || (int) $response['expires'] < 600) {
                            $response = [];
                        }
                    }
                    if (empty($response)) {
                        $response = Curl::to('https://cloudpbx.beeline.ru/apis/portal/subscription')
                            ->withHeader('X-MPBX-API-AUTH-TOKEN: '.$token)
                            ->withData([
                                "pattern" => $channel->call_target_id,
                                "expires" => 86400,
                                "subscriptionType" => "ADVANCED_CALL",
                                "url" => config('app.url').'/api/phones/event'
                            ])
                            ->asJson(true)
                            ->put();
                        if (isset($response['subscriptionId'])) {
                            $channel->update([
                                'call_subscription_id' => $response['subscriptionId'],
                            ]);
                        }
                    }
                }
            }
        );
        $globalSubscription = Configuration::all()->where('name', 'globalCallSubscription')->first();
        $response = [];
        if ($globalSubscription) {
            $response = Curl::to('https://cloudpbx.beeline.ru/apis/portal/subscription')
                ->withHeader('X-MPBX-API-AUTH-TOKEN: '.$token)
                ->withData(['subscriptionId' => $globalSubscription->values])
                ->asJson(true)
                ->get();
            if (!isset($response['expires']) || (int) $response['expires'] < 600) {
                $response = [];
            }
        }
        if (empty($response)) {
            $response = Curl::to('https://cloudpbx.beeline.ru/apis/portal/subscription')
                ->withHeader('X-MPBX-API-AUTH-TOKEN: '.$token)
                ->withData([
                    "expires" => 86400,
                    "subscriptionType" => "ADVANCED_CALL",
                    "url" => config('app.url').'/api/phones/event'
                ])
                ->asJson(true)
                ->put();
            if (isset($response['subscriptionId'])) {
                Configuration::updateOrCreate(['name' => 'globalCallSubscription'], [
                    'values' => $response['subscriptionId'],
                ]);
            }
        }
    }
}
