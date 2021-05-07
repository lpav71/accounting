<?php

namespace App\Console\Commands;

use App\Channel;
use App\Http\Controllers\AnalyticsController;
use App\Order;
use App\Utm;
use App\UtmCampaign;
use App\UtmSource;
use Carbon\Carbon;
use Illuminate\Console\Command;
use GuzzleHttp\Psr7\Uri;

class AnalyticsImport extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'analytics:import';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Import analytics from external sources';

    /**
     * Запуск консольной команды.
     */
    public function handle()
    {
        Channel::query()->whereNotNull('go_proxy_url')->get()->each(
            function (Channel $channel) {
                $headers = [];
                if ($channel->yandex_token) {
                    $headers['Authorization'] = 'OAuth '.$channel->yandex_token;
                }
                if ($channel->yandex_counter) {
                    Order::query()
                        ->where('channel_id', $channel->id)
                        ->whereIn(
                            'utm_id',
                            Utm::whereIn(
                                'utm_campaign_id',
                                UtmCampaign::whereNull('name')->pluck('id')
                            )->pluck('id')
                        )
                        ->whereNotNull('clientID')
                        ->where('created_at', '<=', Carbon::now()->subMinutes(10)->toDateTimeString())
                        ->get()
                        ->each(
                            function (Order $order) use ($channel, $headers) {
                                $dataTo = $order->created_at->toDateString();
                                $dataFrom = $order->created_at->subDays(10)->toDateString();

                                $response = AnalyticsController::requestYandexMetrika(
                                    $channel->go_proxy_url,
                                    [
                                        'ids' => $channel->yandex_counter,
                                        'date1' => $dataFrom,
                                        'date2' => $dataTo,
                                        'metrics' => 'ym:s:visits',
                                        'dimensions' => 'ym:s:UTMCampaign,ym:s:UTMSource,ym:s:refererDomain,ym:s:date',
                                        'filters' => "ym:s:clientID=='{$order->clientID}'",
                                        'sort' => '-ym:s:date',
                                        'group' => 'all',
                                        'quantile' => 100,
                                        'limit' => 1000,
                                        'accuracy' => 'full',
                                    ],
                                    $headers
                                );

                                $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                $response && $response->each(
                                    function (\stdClass $group) use (&$order) {

                                        if (!is_null($group->dimensions['ym:s:UTMCampaign']->name)) {

                                            $order->utm_id =
                                                Utm::firstOrCreate(
                                                    [
                                                        'utm_campaign_id' =>
                                                            (UtmCampaign::firstOrCreate(
                                                                ['name' => $group->dimensions['ym:s:UTMCampaign']->name]
                                                            ))->id,
                                                        'utm_source_id' =>
                                                            (UtmSource::firstOrCreate(
                                                                ['name' => $group->dimensions['ym:s:UTMSource']->name]
                                                            ))->id,
                                                    ]
                                                )->id;

                                            $order->save();

                                            return false;
                                        }

                                        return true;

                                    }
                                );

                                unset($response);


                                if (is_null($order->utm_campaign) && $channel->google_counter && !is_null(
                                        $order->gaClientID
                                    ) && \Storage::exists('keys/google/'.strtolower($channel->name).'.json')) {
                                    // Create and configure a new client object.
                                    $client = new \Google_Client();
                                    $client->setApplicationName($channel->name);
                                    $client->setAuthConfig(
                                        \Storage::path('keys/google/'.strtolower($channel->name).'.json')
                                    );
                                    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
                                    $client->setDefer(true);
                                    $analytics = new \Google_Service_AnalyticsReporting($client);

                                    // Create the DateRange object.
                                    $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
                                    $dateRange->setStartDate($dataFrom);
                                    $dateRange->setEndDate($dataTo);


                                    $dimensionCampaign = new \Google_Service_AnalyticsReporting_Dimension();
                                    $dimensionCampaign->setName('ga:campaign');

                                    $dimensionSource = new \Google_Service_AnalyticsReporting_Dimension();
                                    $dimensionSource->setName('ga:source');

                                    $dimensionDate = new \Google_Service_AnalyticsReporting_Dimension();
                                    $dimensionDate->setName('ga:dateHourMinute');

                                    // Create the Metrics object.
                                    $visits = new \Google_Service_AnalyticsReporting_Metric();
                                    $visits->setExpression("ga:visits");
                                    $visits->setAlias("visits");

                                    $orderBy = new \Google_Service_AnalyticsReporting_OrderBy();
                                    $orderBy->setFieldName('ga:dateHourMinute');


                                    // Create the ReportRequest object.
                                    $request = new \Google_Service_AnalyticsReporting_ReportRequest();
                                    $request->setViewId($channel->google_counter);
                                    $request->setDateRanges($dateRange);
                                    $request->setMetrics([$visits]);
                                    $request->setDimensions([$dimensionCampaign, $dimensionSource, $dimensionDate]);
                                    $request->setFiltersExpression(
                                        "ga:dimension1=={$order->gaClientID};ga:campaign!@(not set)"
                                    );
                                    $request->setPageSize(1);
                                    $request->setOrderBys($orderBy);
                                    try {
                                        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
                                        $body->setReportRequests(array($request));
                                        /**
                                         * @var $google_request \GuzzleHttp\Psr7\Request
                                         */
                                        $google_request = $analytics->reports->batchGet($body);
                                        $expected_class = $google_request->getHeaderLine('X-Php-Expected-Class');
                                        $google_request = $google_request->withoutHeader('X-Php-Expected-Class');
                                        $google_request = $google_request->withHeader(
                                            'Go',
                                            (string)$google_request->getUri()
                                        );
                                        $google_request = $google_request->withUri((new Uri($channel->go_proxy_url)));

                                        $response = $client->execute($google_request, $expected_class);

                                        foreach ($response->getReports() as $report) {
                                            /**
                                             * @var $report \Google_Service_AnalyticsReporting_Report
                                             */
                                            foreach ($report->getData()->getRows() as $row) {

                                                $order->utm_id =
                                                    Utm::firstOrCreate(
                                                        [
                                                            'utm_campaign_id' =>
                                                                (UtmCampaign::firstOrCreate(
                                                                    ['name' => $row->getDimensions()[0]]
                                                                ))->id,
                                                            'utm_source_id' =>
                                                                (UtmSource::firstOrCreate(
                                                                    ['name' => $row->getDimensions()[1]]
                                                                ))->id,
                                                        ]
                                                    )->id;

                                                $order->save();
                                            }
                                        }
                                    } catch (\Exception $e) {

                                    }

                                }

                                if (is_null($order->utm_campaign)) {

                                    $response = AnalyticsController::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'date1' => $dataFrom,
                                            'date2' => $dataTo,
                                            'metrics' => 'ym:s:visits',
                                            'dimensions' => 'ym:s:UTMCampaign,ym:s:UTMSource,ym:s:refererDomain,ym:s:date',
                                            'filters' => "ym:s:clientID=='{$order->clientID}'",
                                            'sort' => '-ym:s:date',
                                            'attribution' => 'first',
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 1000,
                                            'accuracy' => 'full',
                                        ],
                                        $headers
                                    );

                                    $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (&$order) {

                                            if (!is_null($group->dimensions['ym:s:UTMCampaign']->name)) {

                                                $order->utm_id =
                                                    Utm::firstOrCreate(
                                                        [
                                                            'utm_campaign_id' =>
                                                                (UtmCampaign::firstOrCreate(
                                                                    ['name' => $group->dimensions['ym:s:UTMCampaign']->name]
                                                                ))->id,
                                                            'utm_source_id' =>
                                                                (UtmSource::firstOrCreate(
                                                                    ['name' => $group->dimensions['ym:s:UTMSource']->name]
                                                                ))->id,
                                                        ]
                                                    )->id;

                                                $order->save();

                                                return false;
                                            }

                                            return true;

                                        }
                                    );

                                    unset($response);

                                }

                                if (is_null($order->utm_campaign)) {

                                    $response = AnalyticsController::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'date1' => $dataFrom,
                                            'date2' => $dataTo,
                                            'metrics' => 'ym:s:visits',
                                            'dimensions' => 'ym:s:UTMCampaign,ym:s:UTMSource,ym:s:refererDomain,ym:s:date',
                                            'filters' => "ym:s:clientID=='{$order->clientID}'",
                                            'sort' => '-ym:s:date',
                                            'attribution' => 'lastsign',
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 1000,
                                            'accuracy' => 'full',
                                        ],
                                        $headers
                                    );

                                    $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (&$order) {

                                            if (!is_null($group->dimensions['ym:s:UTMCampaign']->name)) {

                                                $order->utm_id =
                                                    Utm::firstOrCreate(
                                                        [
                                                            'utm_campaign_id' =>
                                                                (UtmCampaign::firstOrCreate(
                                                                    ['name' => $group->dimensions['ym:s:UTMCampaign']->name]
                                                                ))->id,
                                                            'utm_source_id' =>
                                                                (UtmSource::firstOrCreate(
                                                                    ['name' => $group->dimensions['ym:s:UTMSource']->name]
                                                                ))->id,
                                                        ]
                                                    )->id;

                                                $order->save();

                                                return false;
                                            }

                                            return true;

                                        }
                                    );

                                    $response && is_null($order->utm_campaign) && $order->update(
                                        [
                                            'utm_id' =>
                                                Utm::firstOrCreate(
                                                    [
                                                        'utm_campaign_id' => '',
                                                        'utm_source_id' => '',
                                                    ]
                                                )->id,
                                        ]
                                    );

                                    unset($response);

                                }

                                usleep(250000);

                            }
                        );

                    Order::query()
                        ->where('channel_id', $channel->id)
                        ->whereIn(
                            'utm_id',
                            Utm::whereIn(
                                'utm_campaign_id',
                                UtmCampaign::where('name', '')->pluck('id')
                            )->pluck('id')
                        )
                        ->whereNull('search_query')
                        ->whereNotNull('clientID')
                        ->where('created_at', '<=', Carbon::now()->subMinutes(10)->toDateTimeString())
                        ->get()
                        ->each(
                            function (Order $order) use ($channel, $headers) {
                                $purchaseID = null;
                                $dataTo = $order->created_at->toDateString();
                                $dataFrom = $order->created_at->subDays(10)->toDateString();

                                $response = AnalyticsController::requestYandexMetrika(
                                    $channel->go_proxy_url,
                                    [
                                        'ids' => $channel->yandex_counter,
                                        'date1' => $dataFrom,
                                        'date2' => $dataTo,
                                        'metrics' => 'ym:s:visits',
                                        'dimensions' => 'ym:s:purchaseID,ym:s:date',
                                        'filters' => "ym:s:clientID=='{$order->clientID}'",
                                        'sort' => '-ym:s:date',
                                        'group' => 'all',
                                        'quantile' => 100,
                                        'limit' => 1000,
                                        'accuracy' => 'full',
                                    ],
                                    $headers
                                );

                                $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                $response && $response->each(
                                    function (\stdClass $group) use (&$purchaseID) {

                                        if (!is_null($group->dimensions['ym:s:purchaseID']->name)) {

                                            $purchaseID = $group->dimensions['ym:s:purchaseID']->name;

                                            return false;
                                        }

                                        return true;

                                    }
                                );

                                $response && is_null($purchaseID) && $order->update(
                                    [
                                        'search_query' => '',
                                    ]
                                );

                                unset($response);

                                if (!is_null($purchaseID)) {

                                    $response = AnalyticsController::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'date1' => $dataFrom,
                                            'date2' => $dataTo,
                                            'metrics' => 'ym:s:visits',
                                            'dimensions' => 'ym:s:firstSearchPhrase,ym:s:date',
                                            'filters' => "ym:s:purchaseID=='{$purchaseID}'",
                                            'sort' => '-ym:s:date',
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 1000,
                                            'accuracy' => 'full',
                                        ],
                                        $headers
                                    );

                                    $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (&$order) {

                                            if (!is_null($group->dimensions['ym:s:firstSearchPhrase']->name)) {

                                                $order->update(
                                                    [
                                                        'search_query' => $group->dimensions['ym:s:firstSearchPhrase']->name,
                                                    ]
                                                );

                                                return false;
                                            }

                                            return true;

                                        }
                                    );

                                    unset($response);

                                    if (is_null($order->search_query)) {

                                        $response = AnalyticsController::requestYandexMetrika(
                                            $channel->go_proxy_url,
                                            [
                                                'ids' => $channel->yandex_counter,
                                                'date1' => $dataFrom,
                                                'date2' => $dataTo,
                                                'metrics' => 'ym:s:visits',
                                                'dimensions' => 'ym:s:lastSearchPhrase,ym:s:date',
                                                'filters' => "ym:s:purchaseID=='{$purchaseID}'",
                                                'sort' => '-ym:s:date',
                                                'group' => 'all',
                                                'quantile' => 100,
                                                'limit' => 1000,
                                                'accuracy' => 'full',
                                            ],
                                            $headers
                                        );

                                        $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                        $response && $response->each(
                                            function (\stdClass $group) use (&$order) {

                                                if (!is_null($group->dimensions['ym:s:lastSearchPhrase']->name)) {

                                                    $order->update(
                                                        [
                                                            'search_query' => $group->dimensions['ym:s:lastSearchPhrase']->name,
                                                        ]
                                                    );

                                                    return false;
                                                }

                                                return true;

                                            }
                                        );

                                        unset($response);

                                    }

                                    if (is_null($order->search_query)) {

                                        $response = AnalyticsController::requestYandexMetrika(
                                            $channel->go_proxy_url,
                                            [
                                                'ids' => $channel->yandex_counter,
                                                'date1' => $dataFrom,
                                                'date2' => $dataTo,
                                                'metrics' => 'ym:s:visits',
                                                'dimensions' => 'ym:s:lastsignSearchPhrase,ym:s:date',
                                                'filters' => "ym:s:purchaseID=='{$purchaseID}'",
                                                'sort' => '-ym:s:date',
                                                'group' => 'all',
                                                'quantile' => 100,
                                                'limit' => 1000,
                                                'accuracy' => 'full',
                                            ],
                                            $headers
                                        );

                                        $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                        $response && $response->each(
                                            function (\stdClass $group) use (&$order) {

                                                if (!is_null($group->dimensions['ym:s:lastsignSearchPhrase']->name)) {

                                                    $order->update(
                                                        [
                                                            'search_query' => $group->dimensions['ym:s:lastsignSearchPhrase']->name,
                                                        ]
                                                    );

                                                    return false;
                                                }

                                                return true;

                                            }
                                        );

                                        $response && is_null($order->search_query) && $order->update(
                                            [
                                                'search_query' => '',
                                            ]
                                        );

                                        unset($response);

                                    }

                                }

                            }
                        );
                }

            }
        );

        $this->parseChannelsOrdersCustomerDevice();
        $this->parseChannelsOrdersCustomerAge();
        $this->parseChannelsOrdersCustomerGender();
    }

    protected function parseChannelsOrdersCustomerDevice()
    {
        Channel::query()
            ->whereNotNull('go_proxy_url')
            ->whereNotNull('yandex_counter')
            ->whereNotNull('yandex_token')
            ->get()
            ->each(
                function (Channel $channel) {

                    $headers = [
                        'Authorization' => 'OAuth '.$channel->yandex_token,
                    ];

                    Order::query()
                        ->where('channel_id', $channel->id)
                        ->whereNotNull('clientID')
                        ->whereNull('device')
                        ->where('created_at', '<=', Carbon::now()->subMinutes(10)->toDateTimeString())
                        ->get()
                        ->each(
                            function (Order $order) use ($headers, $channel) {

                                $dataTo = $order->created_at->toDateString();
                                $dataFrom = $order->created_at->subDays(10)->toDateString();

                                $response = AnalyticsController::requestYandexMetrika(
                                    $channel->go_proxy_url,
                                    [
                                        'ids' => $channel->yandex_counter,
                                        'date1' => $dataFrom,
                                        'date2' => $dataTo,
                                        'metrics' => 'ym:pv:pageviews',
                                        'dimensions' => 'ym:pv:deviceCategory',
                                        'filters' => "ym:s:clientID=='{$order->clientID}'",
                                        'group' => 'all',
                                        'quantile' => 100,
                                        'limit' => 1000,
                                        'accuracy' => 'full',
                                    ],
                                    $headers
                                );

                                $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                $response && $response->each(
                                    function (\stdClass $group) use (&$order) {

                                        if (!is_null($group->dimensions['ym:pv:deviceCategory']->id)) {

                                            $order->update(
                                                [
                                                    'device' => $group->dimensions['ym:pv:deviceCategory']->id,
                                                ]
                                            );

                                            return false;
                                        }

                                        return true;

                                    }
                                );

                                $response && is_null($order->device) && $order->update(
                                    [
                                        'device' => '',
                                    ]
                                );

                                unset($response);
                                usleep(250000);
                            }
                        );


                }
            );
    }

    protected function parseChannelsOrdersCustomerAge()
    {
        Channel::query()
            ->whereNotNull('go_proxy_url')
            ->whereNotNull('yandex_counter')
            ->whereNotNull('yandex_token')
            ->get()
            ->each(
                function (Channel $channel) {

                    $headers = [
                        'Authorization' => 'OAuth '.$channel->yandex_token,
                    ];

                    Order::query()
                        ->where('channel_id', $channel->id)
                        ->whereNotNull('clientID')
                        ->whereNull('age')
                        ->where('created_at', '<=', Carbon::now()->subMinutes(10)->toDateTimeString())
                        ->get()
                        ->each(
                            function (Order $order) use ($channel, $headers) {
                                $purchaseID = null;
                                $dataTo = $order->created_at->toDateString();
                                $dataFrom = $order->created_at->subDays(10)->toDateString();

                                $response = AnalyticsController::requestYandexMetrika(
                                    $channel->go_proxy_url,
                                    [
                                        'ids' => $channel->yandex_counter,
                                        'date1' => $dataFrom,
                                        'date2' => $dataTo,
                                        'metrics' => 'ym:s:visits',
                                        'dimensions' => 'ym:s:purchaseID,ym:s:date',
                                        'filters' => "ym:s:clientID=='{$order->clientID}'",
                                        'sort' => '-ym:s:date',
                                        'group' => 'all',
                                        'quantile' => 100,
                                        'limit' => 1000,
                                        'accuracy' => 'full',
                                    ],
                                    $headers
                                );

                                //TODO Надо бы сохранить purchaseID в заказе

                                $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                $response && $response->each(
                                    function (\stdClass $group) use (&$purchaseID) {

                                        if (!is_null($group->dimensions['ym:s:purchaseID']->name)) {

                                            $purchaseID = $group->dimensions['ym:s:purchaseID']->name;

                                            return false;
                                        }

                                        return true;

                                    }
                                );

                                $response && is_null($purchaseID) && $order->update(
                                    [
                                        'age' => '',
                                    ]
                                );

                                unset($response);

                                if (!is_null($purchaseID)) {

                                    $response = AnalyticsController::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'date1' => $dataFrom,
                                            'date2' => $dataTo,
                                            'metrics' => 'ym:pv:pageviews',
                                            'dimensions' => 'ym:pv:ageInterval',
                                            'filters' => "ym:s:purchaseID=='{$purchaseID}'",
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 1000,
                                            'accuracy' => 'full',
                                        ],
                                        $headers
                                    );

                                    $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (&$order) {

                                            if (!is_null($group->dimensions['ym:pv:ageInterval']->id)) {

                                                $order->update(
                                                    [
                                                        'age' => $group->dimensions['ym:pv:ageInterval']->id,
                                                    ]
                                                );

                                                return false;
                                            }

                                            return true;

                                        }
                                    );

                                    $response && is_null($order->age) && $order->update(
                                        [
                                            'age' => '',
                                        ]
                                    );

                                    unset($response);

                                }
                                usleep(250000);

                            }
                        );

                }
            );
    }

    protected function parseChannelsOrdersCustomerGender()
    {
        Channel::query()
            ->whereNotNull('go_proxy_url')
            ->whereNotNull('yandex_counter')
            ->whereNotNull('yandex_token')
            ->get()
            ->each(
                function (Channel $channel) {

                    $headers = [
                        'Authorization' => 'OAuth '.$channel->yandex_token,
                    ];

                    Order::query()
                        ->where('channel_id', $channel->id)
                        ->whereNotNull('clientID')
                        ->whereNull('gender')
                        ->where('created_at', '<=', Carbon::now()->subMinutes(10)->toDateTimeString())
                        ->get()
                        ->each(
                            function (Order $order) use ($channel, $headers) {
                                $purchaseID = null;
                                $dataTo = $order->created_at->toDateString();
                                $dataFrom = $order->created_at->subDays(10)->toDateString();

                                $response = AnalyticsController::requestYandexMetrika(
                                    $channel->go_proxy_url,
                                    [
                                        'ids' => $channel->yandex_counter,
                                        'date1' => $dataFrom,
                                        'date2' => $dataTo,
                                        'metrics' => 'ym:s:visits',
                                        'dimensions' => 'ym:s:purchaseID,ym:s:date',
                                        'filters' => "ym:s:clientID=='{$order->clientID}'",
                                        'sort' => '-ym:s:date',
                                        'group' => 'all',
                                        'quantile' => 100,
                                        'limit' => 1000,
                                        'accuracy' => 'full',
                                    ],
                                    $headers
                                );

                                //TODO Надо бы сохранить purchaseID в заказе

                                $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                $response && $response->each(
                                    function (\stdClass $group) use (&$purchaseID) {

                                        if (!is_null($group->dimensions['ym:s:purchaseID']->name)) {

                                            $purchaseID = $group->dimensions['ym:s:purchaseID']->name;

                                            return false;
                                        }

                                        return true;

                                    }
                                );

                                $response && is_null($purchaseID) && $order->update(
                                    [
                                        'gender' => '',
                                    ]
                                );

                                unset($response);

                                if (!is_null($purchaseID)) {

                                    $response = AnalyticsController::requestYandexMetrika(
                                        $channel->go_proxy_url,
                                        [
                                            'ids' => $channel->yandex_counter,
                                            'date1' => $dataFrom,
                                            'date2' => $dataTo,
                                            'metrics' => 'ym:pv:pageviews',
                                            'dimensions' => 'ym:pv:gender',
                                            'filters' => "ym:s:purchaseID=='{$purchaseID}'",
                                            'group' => 'all',
                                            'quantile' => 100,
                                            'limit' => 1000,
                                            'accuracy' => 'full',
                                        ],
                                        $headers
                                    );

                                    $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                                    $response && $response->each(
                                        function (\stdClass $group) use (&$order) {

                                            if (!is_null($group->dimensions['ym:pv:gender']->id)) {

                                                $order->update(
                                                    [
                                                        'gender' => $group->dimensions['ym:pv:gender']->id,
                                                    ]
                                                );

                                                return false;
                                            }

                                            return true;

                                        }
                                    );

                                    $response && is_null($order->gender) && $order->update(
                                        [
                                            'gender' => '',
                                        ]
                                    );

                                    unset($response);

                                }
                                usleep(250000);
                            }
                        );

                }
            );
    }
}
