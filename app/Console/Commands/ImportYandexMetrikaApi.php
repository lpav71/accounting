<?php

namespace App\Console\Commands;

use App\Channel;
use App\Http\Controllers\AnalyticsController;
use App\Vendors\ClickHouse\Client;
use Carbon\Carbon;
use ClickHouseDB\Type\UInt64;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Monolog\Logger;

/**
 * Команда добавления недостающих статистических данных из Яндекс.Метрики
 *
 * @package App\Console\Commands
 */
class ImportYandexMetrikaApi extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'analytics:add-metrika-api';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Добавление недостающих статистических данных из Яндекс.Метрики';

    /**
     * Суточный лимит запросов счетчика Яндекс
     */
    protected const YA_COUNTER_LIMIT = 2250;

    /**
     * Возможные значения возраста Яндекс.Метрики
     */
    protected const YA_AGE_INTERVALS = ['17', '18', '25', '35', '45', '55'];

    /**
     * Возможные значения пола Яндекс.Метрики
     */
    protected const YA_GENDERS = ['male', 'female'];

    /**
     * Таблица хранения количества запросов к счетчику за сутки
     *
     * @var string
     */
    protected $limitsTable = 'counter_limits';

    /**
     * Конфигурация базы данных
     * @var array
     */
    protected $dbConfig = [];

    /**
     * Клиент базы данных
     * @var Client
     */
    protected $dbClient;

    /**
     * Логгер
     * @var Logger
     */
    protected $logger;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();

        $this->dbConfig = [
            'host' => config('clickhouse.host'),
            'port' => config('clickhouse.port'),
            'username' => config('clickhouse.username'),
            'password' => config('clickhouse.password'),
        ];

    }

    /**
     * Запуск консольной команды.
     */
    public function handle()
    {
        $this->dbClient = new Client($this->dbConfig);
        $this->dbClient->settings()->max_execution_time(200);

        $this->logger = new Logger('Analytics.YandexMetrikaLogs');

        Channel::all()->each(
            function (Channel $channel) {

                $channel->go_proxy_url = null;

                $token = (string)($channel->yandex_token ?? '');
                $counter = (string)($channel->yandex_counter ?? '');

                $this->logger->debug("Источник {$channel->name}.", compact('token', 'counter'));

                if ($token == '' || $counter == '') {
                    $this->logger->debug("Источник {$channel->name}: нет токена или счетчика.");

                    return true;
                }

                $date = Carbon::now()->toDateString();

                $requests = DB::query()
                        ->from($this->limitsTable)
                        ->where('date', $date)
                        ->where('counter', $counter)
                        ->where('is_ya_counter', 1)
                        ->value('requests') ?? 0;

                if ($requests > self::YA_COUNTER_LIMIT) {
                    $this->logger->debug("Источник {$channel->name}: превышен суточный лимит запросов к счетчику.");

                    return true;
                }

                $headers = [];
                $headers['Authorization'] = "OAuth {$token}";

                $db = $this->dbClient;

                $dbName = "db_{$counter}_ya_counter";

                if (!$db->isDatabaseExist($dbName)) {
                    $this->logger->debug("Источник {$channel->name}: отсутствует база данных {$dbName}.");

                    return true;
                }

                $db->database($dbName);

                if (!$db->isExists($dbName, 'hits')) {
                    $this->logger->debug("Источник {$channel->name}: отсутствует таблица {$dbName}.hits.");

                    return true;
                }

                if (!$db->isExists($dbName, 'visits')) {
                    $this->logger->debug("Источник {$channel->name}: отсутствует таблица {$dbName}.visits.");

                    return true;
                }

                if (!$db->isExists($dbName, 'additional')) {
                    $sql = "CREATE TABLE {$dbName}.additional (
                                    Date Date,
                                    WatchID UInt64,
                                    ClientID UInt64,
                                    AgeInterval String,
                                    Gender String
                                ) ENGINE = MergeTree(Date, intHash32(ClientID), (Date, intHash32(ClientID)), 8192)";

                    $db->write($sql);
                }

                if (!$db->isExists($dbName, 'costs')) {
                    $sql = "CREATE TABLE {$dbName}.costs (
                                    Date Date,
                                    Id UUID MATERIALIZED generateUUIDv4(),
                                    DateTime DateTime,
                                    WatchID UInt64,
                                    ClientID UInt64,
                                    UTMCampaign String,
                                    UTMSource String,
                                    Source String,
                                    Cost UInt32
                                ) ENGINE = MergeTree(Date, Id, (Date, Id), 8192)";

                    $db->write($sql);
                }


                while ($requests < self::YA_COUNTER_LIMIT) {

                    $clientID = $db
                        ->select(
                            "SELECT h.ClientID FROM hits h LEFT JOIN additional a ON (a.ClientID = h.ClientID AND a.Date = h.Date) WHERE a.AgeInterval = '' ORDER BY h.Date DESC LIMIT 1"
                        )
                        ->fetchOne('ClientID');

                    $dateParseYa = $db
                        ->select(
                            "SELECT h.Date FROM hits h LEFT JOIN costs c ON (c.Date = h.Date AND c.Source = 'yandex') WHERE c.Source = '' AND h.UTMSource = 'yandex' ORDER BY h.Date DESC LIMIT 1"
                        )
                        ->fetchOne('Date');

                    $dateParseGo = $db
                        ->select(
                            "SELECT h.Date FROM hits h LEFT JOIN costs c ON (c.Date = h.Date AND c.Source = 'google') WHERE c.Source = '' AND h.UTMSource = 'google' ORDER BY h.Date DESC LIMIT 1"
                        )
                        ->fetchOne('Date');

                    if (is_null($clientID) && is_null($dateParseYa) && is_null($dateParseGo)) {
                        $this->logger->debug("Источник {$channel->name} полностью обработан.");

                        return true;
                    }

                    if (is_null($clientID)) {
                        goto Yandex;
                    }


                    $watchIDsResult = $db
                        ->select("SELECT DISTINCT Date, WatchID FROM hits WHERE ClientID = {$clientID}")
                        ->rows();

                    $watchIDs = [];
                    $startDate = $finishDate = null;

                    foreach ($watchIDsResult as $row) {
                        $watchIDs[] = "'{$row['WatchID']}'";
                        $row['Date'] = Carbon::createFromFormat('Y-m-d', $row['Date'])->getTimestamp();

                        if (is_null($startDate)) {
                            $startDate = $finishDate = $row['Date'];
                        } else {
                            $startDate = $row['Date'] < $startDate ? $row['Date'] : $startDate;
                            $finishDate = $row['Date'] > $finishDate ? $row['Date'] : $finishDate;
                        }
                    }

                    $startDate = date('Y-m-d', $startDate ?? time());
                    $finishDate = date('Y-m-d', $finishDate ?? time());

                    $ageInterval = '0';
                    $gender = 'unknown';
                    $dataToSave = [];

                    $watchIDsChunks = array_chunk($watchIDs, 100);

                    foreach (self::YA_AGE_INTERVALS as $currentAge) {

                        foreach ($watchIDsChunks as $watchIDs) {
                            $watchIDs = implode(',', $watchIDs);

                            if ($requests > self::YA_COUNTER_LIMIT) {
                                $this->logger->debug(
                                    "Источник {$channel->name}: превышен суточный лимит запросов к счетчику."
                                );

                                return true;
                            }

                            $requests++;

                            $this->saveCounterRequests($date, $counter, $requests);

                            try {

                                $params = [
                                    'ids' => $channel->yandex_counter,
                                    'date1' => $startDate,
                                    'date2' => $finishDate,
                                    'metrics' => 'ym:s:pageviews',
                                    'dimensions' => 'ym:s:clientID',
                                    'filters' => "ym:pv:ageInterval=={$currentAge} AND ym:pv:watchID=.({$watchIDs})",
                                    'group' => 'all',
                                    'quantile' => 100,
                                    'limit' => 1000,
                                    'accuracy' => 'full',
                                ];

                                $response = AnalyticsController::requestYandexMetrika(
                                    $channel->go_proxy_url,
                                    $params,
                                    $headers
                                );

                                if ($response->code != '200' || !($response->body instanceof \stdClass)) {
                                    $this->logger->debug(
                                        "Источник {$channel->name}. Ошибка Яндекс.Метрики.",
                                        compact('params')
                                    );
                                    throw new \Exception("Ошибка Яндекс.Метрики. Код {$response->code}.");
                                }

                                if (!property_exists($response->body, 'totals')
                                    || !is_array($response->body->totals)
                                    || !count($response->body->totals)
                                    || intval($response->body->totals[0]) == 0
                                ) {
                                    continue;
                                } else {
                                    $ageInterval = $currentAge;

                                    break 2;
                                }


                            } catch (\Exception $e) {
                                $this->logger->debug($e->getMessage(), $e->getTrace());

                                return true;
                            }
                        }


                    }

                    foreach (self::YA_GENDERS as $currentGender) {

                        foreach ($watchIDsChunks as $watchIDs) {
                            $watchIDs = implode(',', $watchIDs);
                            if ($requests > self::YA_COUNTER_LIMIT) {
                                $this->logger->debug(
                                    "Источник {$channel->name}: превышен суточный лимит запросов к счетчику."
                                );

                                return true;
                            }

                            $requests++;

                            $this->saveCounterRequests($date, $counter, $requests);

                            try {

                                $params = [
                                    'ids' => $channel->yandex_counter,
                                    'date1' => $startDate,
                                    'date2' => $finishDate,
                                    'metrics' => 'ym:s:pageviews',
                                    'dimensions' => 'ym:s:clientID',
                                    'filters' => "ym:pv:gender=='{$currentGender}' AND ym:pv:watchID=.({$watchIDs})",
                                    'group' => 'all',
                                    'quantile' => 100,
                                    'limit' => 1000,
                                    'accuracy' => 'full',
                                ];

                                $response = AnalyticsController::requestYandexMetrika(
                                    $channel->go_proxy_url,
                                    $params,
                                    $headers
                                );

                                if ($response->code != '200' || !($response->body instanceof \stdClass)) {
                                    $this->logger->debug(
                                        "Источник {$channel->name}. Ошибка Яндекс.Метрики.",
                                        compact('params')
                                    );
                                    throw new \Exception("Ошибка Яндекс.Метрики. Код {$response->code}.");
                                }

                                if (!property_exists($response->body, 'totals')
                                    || !is_array($response->body->totals)
                                    || !count($response->body->totals)
                                    || intval($response->body->totals[0]) == 0
                                ) {
                                    continue;
                                }

                                $gender = $currentGender;

                                break 2;


                            } catch (\Exception $e) {
                                $this->logger->debug($e->getMessage(), $e->getTrace());

                                return true;
                            }

                        }


                    }

                    foreach ($watchIDsResult as $row) {
                        $dataToSave[] = [
                            'Date' => $row['Date'],
                            'WatchID' => UInt64::fromString($row['WatchID']),
                            'ClientID' => UInt64::fromString($clientID),
                            'AgeInterval' => $ageInterval,
                            'Gender' => $gender,
                        ];
                    }

                    try {
                        !empty($dataToSave) && $db->insert('additional', $dataToSave);
                    } catch (\Exception $e) {
                        $this->logger->debug($e->getMessage(), $e->getTrace());

                        goto Yandex;
                    }

                    $this->logger->debug("Источник {$channel->name}. Сохранены данные.", compact('dataToSave'));

                    //Запрос незаполненной даты по расходам

                    Yandex:
                    $dataToSave = [];

                    $dateParse = $db
                        ->select(
                            "SELECT h.Date FROM hits h LEFT JOIN costs c ON (c.Date = h.Date AND c.Source = 'yandex') WHERE c.Source = '' AND h.UTMSource = 'yandex' ORDER BY h.Date DESC LIMIT 1"
                        )
                        ->fetchOne('Date');

                    if (is_null($dateParse)) {
                        goto Google;
                    }

                    $requests++;

                    $this->saveCounterRequests($date, $counter, $requests);

                    $response = AnalyticsController::requestYandexMetrika(
                        $channel->go_proxy_url,
                        [
                            'counters' => $channel->yandex_counter,
                        ],
                        $headers,
                        'management'
                    );

                    $clients = AnalyticsController::parseResponseYandexMetrikaOrFail($response, 'management');

                    if (!$clients) {
                        goto Google;
                    }

                    $directChiefLogins = [];

                    foreach ($clients as $client) {
                        $directChiefLogins[] = $client->chief_login;
                    }

                    $directChiefLogins = implode(',', $directChiefLogins);

                    $requests++;

                    $this->saveCounterRequests($date, $counter, $requests);

                    $response = AnalyticsController::requestYandexMetrika(
                        $channel->go_proxy_url,
                        [
                            'ids' => $channel->yandex_counter,
                            'date1' => $dateParse,
                            'date2' => $dateParse,
                            'metrics' => 'ym:s:pageviews',
                            'dimensions' => 'ym:s:lastDirectClickBanner,ym:s:UTMCampaign,ym:s:UTMSource',
                            'group' => 'all',
                            'quantile' => 100,
                            'limit' => 100000,
                            'accuracy' => 'full',
                            'sort' => 'ym:s:pageviews',
                        ],
                        $headers
                    );

                    $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                    if (!$response instanceof Collection) {
                        goto Google;
                    }

                    $directBanners = [];

                    $response->each(
                        function (\stdClass $group) use (&$directBanners) {
                            $directBanners[$group->dimensions['ym:s:lastDirectClickBanner']->direct_id] = [
                                'UTMCampaign' => $group->dimensions['ym:s:UTMCampaign']->name,
                                'UTMSource' => $group->dimensions['ym:s:UTMSource']->name,
                            ];
                        }
                    );

                    $requests++;

                    $this->saveCounterRequests($date, $counter, $requests);

                    $response = AnalyticsController::requestYandexMetrika(
                        $channel->go_proxy_url,
                        [
                            'ids' => $channel->yandex_counter,
                            'direct_client_logins' => $directChiefLogins,
                            'date1' => $dateParse,
                            'date2' => $dateParse,
                            'metrics' => 'ym:ad:USDAdCost,ym:ad:clicks',
                            'dimensions' => 'ym:ad:dateTime,ym:ad:directBanner',
                            'group' => 'all',
                            'quantile' => 100,
                            'limit' => 100000,
                            'accuracy' => 'full',
                            'sort' => 'ym:ad:dateTime',
                            'filter' => 'ym:ad:clicks > 0',
                        ],
                        $headers
                    );

                    $response = AnalyticsController::parseResponseYandexMetrikaOrFail($response);

                    if (!$response instanceof Collection) {
                        goto Google;
                    }

                    $response->each(
                        function (\stdClass $group) use ($db, $directBanners, $dateParse, &$dataToSave) {

                            $clicks = intval($group->metrics['ym:ad:clicks']);

                            if (!isset($directBanners[$group->dimensions['ym:ad:directBanner']->direct_id])) {

                                while ($clicks > 0) {
                                    $dataToSave[] = [
                                        'Date' => $dateParse,
                                        'DateTime' => $group->dimensions['ym:ad:dateTime']->name,
                                        'WatchID' => 0,
                                        'ClientID' => 0,
                                        'UTMCampaign' => '',
                                        'UTMSource' => '',
                                        'Source' => 'yandex',
                                        'Cost' => round(
                                            floatval($group->metrics['ym:ad:USDAdCost']) / intval(
                                                $group->metrics['ym:ad:clicks']
                                            ) * 100000
                                        ),
                                    ];

                                    $clicks--;
                                }

                                return true;
                            }

                            $utmCampaign = $directBanners[$group->dimensions['ym:ad:directBanner']->direct_id]['UTMCampaign'];
                            $utmSource = $directBanners[$group->dimensions['ym:ad:directBanner']->direct_id]['UTMSource'];
                            $dateTime = Carbon::createFromFormat(
                                'Y-m-d H:i:s',
                                $group->dimensions['ym:ad:dateTime']->name
                            )->getTimestamp()-30;
                            $dateTimeTo = $dateTime + 180;

                            $hit = $db
                                ->select(
                                    "SELECT * FROM hits
                                            WHERE UTMCampaign = '{$utmCampaign}' AND UTMSource = '{$utmSource}' AND DateTime >= toDateTime({$dateTime}) AND DateTime <= toDateTime({$dateTimeTo})
                                            ORDER BY DateTime DESC LIMIT 1"
                                )
                                ->fetchOne();

                            if (is_null($hit)) {
                                while ($clicks > 0) {
                                    $dataToSave[] = [
                                        'Date' => $dateParse,
                                        'DateTime' => $group->dimensions['ym:ad:dateTime']->name,
                                        'WatchID' => 0,
                                        'ClientID' => 0,
                                        'UTMCampaign' => $utmCampaign ?? '',
                                        'UTMSource' => $utmSource ?? '',
                                        'Source' => 'yandex',
                                        'Cost' => round(
                                            floatval($group->metrics['ym:ad:USDAdCost']) / intval(
                                                $group->metrics['ym:ad:clicks']
                                            ) * 100000
                                        ),
                                    ];

                                    $clicks--;
                                }

                                return true;
                            }

                            while ($clicks > 0) {
                                $dataToSave[] = [
                                    'Date' => $dateParse,
                                    'DateTime' => $group->dimensions['ym:ad:dateTime']->name,
                                    'WatchID' => UInt64::fromString($hit['WatchID']),
                                    'ClientID' => UInt64::fromString($hit['ClientID']),
                                    'UTMCampaign' => $utmCampaign ?? '',
                                    'UTMSource' => $utmSource ?? '',
                                    'Source' => 'yandex',
                                    'Cost' => (int)round(
                                        floatval($group->metrics['ym:ad:USDAdCost']) / intval(
                                            $group->metrics['ym:ad:clicks']
                                        ) * 100000
                                    ),
                                ];

                                $clicks--;
                            }

                            return true;

                        }
                    );

                    if (empty($dataToSave)) {
                        $dataToSave[] = [
                            'Date' => $dateParse,
                            'DateTime' => date_create_from_format('Y-m-d', $dateParse)->format('Y-m-d H:i:s'),
                            'WatchID' => 0,
                            'ClientID' => 0,
                            'UTMCampaign' => '',
                            'UTMSource' => '',
                            'Source' => 'yandex',
                            'Cost' => 0,
                        ];
                    }

                    try {
                        $db->insert('costs', $dataToSave);
                    } catch (\Exception $e) {
                        $this->logger->debug($e->getMessage(), $e->getTrace());

                        goto Google;
                    }

                    $this->logger->debug("Источник {$channel->name}. Сохранены данные.", compact('dataToSave'));


                    Google:
                    if ($channel->google_counter
                        && \Storage::exists('keys/google/'.strtolower($channel->name).'.json')) {

                        $dateParse = $db
                            ->select(
                                "SELECT h.Date FROM hits h LEFT JOIN costs c ON (c.Date = h.Date AND c.Source = 'google') WHERE c.Source = '' AND h.UTMSource = 'google' ORDER BY h.Date DESC LIMIT 1"
                            )
                            ->fetchOne('Date');

                        if (is_null($dateParse)) {
                            continue;
                        }

                        $dataToSave = [];

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
                        $dateRange->setStartDate($dateParse);
                        $dateRange->setEndDate($dateParse);

                        $dimensionTime = new \Google_Service_AnalyticsReporting_Dimension();
                        $dimensionTime->setName('ga:dateHourMinute');

                        $dimensionCampaign = new \Google_Service_AnalyticsReporting_Dimension();
                        $dimensionCampaign->setName('ga:campaign');

                        $dimensionSource = new \Google_Service_AnalyticsReporting_Dimension();
                        $dimensionSource->setName('ga:source');

                        $dimensionSourceMedium = new \Google_Service_AnalyticsReporting_Dimension();
                        $dimensionSourceMedium->setName('ga:sourceMedium');

                        // Create the Metrics object.
                        $pageViews = new \Google_Service_AnalyticsReporting_Metric();
                        $pageViews->setExpression("ga:pageviews");
                        $pageViews->setAlias("pageviews");

                        $order = new \Google_Service_AnalyticsReporting_OrderBy();
                        $order->setFieldName('ga:dateHourMinute');

                        // Create the ReportRequest object.
                        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
                        $request->setViewId($channel->google_counter);
                        $request->setDateRanges($dateRange);
                        $request->setMetrics([$pageViews]);
                        $request->setDimensions(
                            [$dimensionTime, $dimensionCampaign, $dimensionSource, $dimensionSourceMedium]
                        );
                        $request->setOrderBys($order);
                        $request->setSamplingLevel('LARGE');
                        $request->setPageSize(100000);



                        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
                        $body->setReportRequests([$request]);
                        /**
                         * @var $google_request \GuzzleHttp\Psr7\Request
                         */
                        $google_request = $analytics->reports->batchGet($body);

                        try {
                            $response = $client->execute($google_request);
                        } catch (\Exception $e) {
                            continue;
                        }

                        $this->logger->debug("Источник {$channel->name}. Получены данные.", $response->getReports());

                        $googleVisits = [];

                        foreach ($response->getReports() as $report) {
                            /**
                             * @var $report \Google_Service_AnalyticsReporting_Report
                             */
                            foreach ($report->getData()->getRows() as $row) {
                                /**
                                 * @var $row \Google_Service_AnalyticsReporting_ReportRow
                                 */
                                $googleTime = $row->getDimensions()[0];
                                $googleCampaign = $row->getDimensions()[1];
                                $googleSource = $row->getDimensions()[2];
                                $googleSourceMedium = $row->getDimensions()[3];

                                if ($googleSource == 'google' && strstr($googleSourceMedium, 'cpc')) {
                                    $googleVisits[] = [
                                        'time' => date_create_from_format('YmdHi', $googleTime),
                                        'campaign' => $googleCampaign,
                                        'source' => $googleSource,
                                        'sourceMedium' => $googleSourceMedium,
                                    ];
                                }

                            }
                        }

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
                        $dateRange->setStartDate($dateParse);
                        $dateRange->setEndDate($dateParse);

                        $dimensionCampaign = new \Google_Service_AnalyticsReporting_Dimension();
                        $dimensionCampaign->setName('ga:campaign');

                        // Create the Metrics object.
                        $clicks = new \Google_Service_AnalyticsReporting_Metric();
                        $clicks->setExpression("ga:adClicks");
                        $clicks->setAlias("clicks");

                        // Create the Metrics object.
                        $costs = new \Google_Service_AnalyticsReporting_Metric();
                        $costs->setExpression("ga:adCost");
                        $costs->setAlias("cost");

                        // Create the ReportRequest object.
                        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
                        $request->setViewId($channel->google_counter);
                        $request->setDateRanges($dateRange);
                        $request->setMetrics([$clicks, $costs]);
                        $request->setDimensions([$dimensionCampaign]);
                        $request->setSamplingLevel('LARGE');
                        $request->setPageSize(100000);

                        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
                        $body->setReportRequests([$request]);
                        /**
                         * @var $google_request \GuzzleHttp\Psr7\Request
                         */
                        $google_request = $analytics->reports->batchGet($body);
                        try {
                            $response = $client->execute($google_request);
                        } catch (\Exception $e) {
                            $this->logger->debug("Источник {$channel->name}. Получены данные.", $e->getMessage());
                            continue;
                        }

                        $this->logger->debug("Источник {$channel->name}. Получены данные.", $response->getReports());

                        $googleCampaigns = [];

                        foreach ($response->getReports() as $report) {
                            /**
                             * @var $report \Google_Service_AnalyticsReporting_Report
                             */
                            foreach ($report->getData()->getRows() as $row) {
                                /**
                                 * @var $row \Google_Service_AnalyticsReporting_ReportRow
                                 */
                                $googleCampaign = $row->getDimensions()[0];

                                /**
                                 * @var $metric \Google_Service_AnalyticsReporting_DateRangeValues
                                 */
                                $metric = $row->getMetrics()[0];
                                $googleClicks = (int)$metric->getValues()[0];
                                if ($googleClicks < 1) {
                                    continue;
                                }
                                $googleCostPerClick = ((float)$metric->getValues()[1] * 1.2) / $googleClicks;

                                $googleCampaigns[$googleCampaign] = [
                                    'campaign' => $googleCampaign,
                                    'clicks' => $googleClicks,
                                    'costPerClick' => $googleCostPerClick,
                                ];

                            }
                        }

                        foreach ($googleVisits as $key => &$visit) {
                            if (isset($googleCampaigns[$visit['campaign']])) {
                                $visit['cost'] = $googleCampaigns[$visit['campaign']]['costPerClick'];
                                $googleCampaigns[$visit['campaign']]['clicks']--;

                                if ($googleCampaigns[$visit['campaign']]['clicks'] < 1) {
                                    unset($googleCampaigns[$visit['campaign']]);
                                }
                            } else {
                                unset($googleVisits[$key]);
                            }
                        }

                        foreach ($googleCampaigns as $key => $googleCampaign) {

                            while($googleCampaign['clicks'] > 0) {
                                $googleVisits[] = [
                                    'time' => date_create_from_format('Y-m-d', $dateParse),
                                    'campaign' => $googleCampaign['campaign'],
                                    'source' => 'google',
                                    'sourceMedium' => 'google',
                                    'cost' => $googleCampaign['costPerClick'],
                                ];

                                $googleCampaign['clicks']--;
                            }

                            unset($googleCampaigns[$key]);
                        }


                        foreach ($googleVisits as $visit) {

                            $dateTime = $visit['time']->getTimeStamp() - 30;
                            $dateTimeTo = $dateTime + 180;

                            $sqlQuery = "SELECT * FROM hits
                                            WHERE UTMCampaign = '{$visit['campaign']}' AND UTMSource = '{$visit['source']}' AND DateTime >= toDateTime({$dateTime}) AND DateTime <= toDateTime({$dateTimeTo})
                                            ORDER BY DateTime DESC LIMIT 1";

                            $hit = $db
                                ->select($sqlQuery)
                                ->fetchOne();

                            $this->logger->debug("Источник {$channel->name}. sql", compact('sqlQuery'));
                            $this->logger->debug("Источник {$channel->name}. visit", compact('visit'));
                            $this->logger->debug("Источник {$channel->name}. hit", compact('hit'));

                            if (is_null($hit)) {
                                $dataToSave[] = [
                                    'Date' => $dateParse,
                                    'DateTime' => date('Y-m-d H:i:s', $dateTime),
                                    'WatchID' => 0,
                                    'ClientID' => 0,
                                    'UTMCampaign' => $visit['campaign'] ?? '',
                                    'UTMSource' => $visit['source'] ?? '',
                                    'Source' => 'google',
                                    'Cost' => round($visit['cost'] * 100000),
                                ];

                                continue;
                            }

                            $dataToSave[] = [
                                'Date' => $dateParse,
                                'DateTime' => date('Y-m-d H:i:s', $dateTime),
                                'WatchID' => UInt64::fromString($hit['WatchID']),
                                'ClientID' => UInt64::fromString($hit['ClientID']),
                                'UTMCampaign' => $visit['campaign'] ?? '',
                                'UTMSource' => $visit['source'] ?? '',
                                'Source' => 'google',
                                'Cost' => round($visit['cost'] * 100000),
                            ];
                        }

                        if (empty($dataToSave)) {
                            $dataToSave[] = [
                                'Date' => $dateParse,
                                'DateTime' => date_create_from_format('Y-m-d', $dateParse)->format('Y-m-d H:i:s'),
                                'WatchID' => 0,
                                'ClientID' => 0,
                                'UTMCampaign' => '',
                                'UTMSource' => '',
                                'Source' => 'google',
                                'Cost' => 0,
                            ];
                        }

                        try {
                            $db->insert('costs', $dataToSave);
                        } catch (\Exception $e) {
                            $this->logger->debug($e->getMessage(), $e->getTrace());

                            return true;
                        }

                        $this->logger->debug("Источник {$channel->name}. Сохранены данные.", compact('dataToSave'));

                    }

                }

                return true;
            }
        );
    }

    /**
     * Сохранение количества запросов к счетчику в БД
     *
     * @param string $date
     * @param string $counter
     * @param int $requests
     * @param bool $isYaCounter
     */
    protected function saveCounterRequests(string $date, string $counter, int $requests, bool $isYaCounter = true): void
    {
        DB::query()
            ->from($this->limitsTable)
            ->updateOrInsert(
                ['date' => $date, 'counter' => $counter, 'is_ya_counter' => (int)$isYaCounter],
                ['requests' => $requests]
            );
    }
}
