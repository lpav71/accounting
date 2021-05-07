<?php

namespace App\Console\Commands;

use App\Channel;
use App\Vendors\ClickHouse\Client;
use App\Vendors\GuzzleHttp\GoProxyClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Monolog\Logger;
use Unirest;
use Volga\MetrikaLogs\MetrikaClient;
use Volga\MetrikaLogs\Requests\CancelRequest;
use Volga\MetrikaLogs\Requests\CapabilityRequest;
use Volga\MetrikaLogs\Requests\CleanRequest;
use Volga\MetrikaLogs\Requests\CreateRequest;
use Volga\MetrikaLogs\Requests\DownloadRequest;
use Volga\MetrikaLogs\Requests\InformationRequest;
use Volga\MetrikaLogs\Requests\LogListRequest;
use Volga\MetrikaLogs\Responses\Types\LogRequest;
use Volga\MetrikaLogs\Responses\Types\LogRequestPart;

/**
 * Команда получения сырых данных статистистики по источникам через API Яндекс.Метрика Logs
 *
 * @package App\Console\Commands
 */
class ImportYandexMetrikaLogs extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'analytics:get-metrika-logs';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Получение сырых данных статистистики по источникам через API Яндекс.Метрика Logs';

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
     * Доступные поля в API
     *
     * @var array
     */
    protected $fields = [
        'hits' => [
            'ym:pv:watchID' => 'UInt64',
            'ym:pv:counterID' => 'UInt32',
            'ym:pv:dateTime' => 'DateTime',
            'ym:pv:title' => 'String',
            'ym:pv:URL' => 'String',
            'ym:pv:referer' => 'String',
            'ym:pv:UTMCampaign' => 'String',
            'ym:pv:UTMContent' => 'String',
            'ym:pv:UTMMedium' => 'String',
            'ym:pv:UTMSource' => 'String',
            'ym:pv:UTMTerm' => 'String',
            'ym:pv:browser' => 'String',
            'ym:pv:browserMajorVersion' => 'UInt16',
            'ym:pv:browserMinorVersion' => 'UInt16',
            'ym:pv:browserCountry' => 'String',
            'ym:pv:browserEngine' => 'String',
            'ym:pv:browserEngineVersion1' => 'UInt16',
            'ym:pv:browserEngineVersion2' => 'UInt16',
            'ym:pv:browserEngineVersion3' => 'UInt16',
            'ym:pv:browserEngineVersion4' => 'UInt16',
            'ym:pv:browserLanguage' => 'String',
            'ym:pv:clientTimeZone' => 'Int16',
            'ym:pv:cookieEnabled' => 'UInt8',
            'ym:pv:deviceCategory' => 'String',
            'ym:pv:flashMajor' => 'UInt8',
            'ym:pv:flashMinor' => 'UInt8',
            'ym:pv:from' => 'String',
            'ym:pv:hasGCLID' => 'UInt8',
            'ym:pv:ipAddress' => 'String',
            'ym:pv:javascriptEnabled' => 'UInt8',
            'ym:pv:mobilePhone' => 'String',
            'ym:pv:mobilePhoneModel' => 'String',
            'ym:pv:openstatAd' => 'String',
            'ym:pv:openstatCampaign' => 'String',
            'ym:pv:openstatService' => 'String',
            'ym:pv:openstatSource' => 'String',
            'ym:pv:operatingSystem' => 'String',
            'ym:pv:operatingSystemRoot' => 'String',
            'ym:pv:physicalScreenHeight' => 'UInt16',
            'ym:pv:physicalScreenWidth' => 'UInt16',
            'ym:pv:regionCity' => 'String',
            'ym:pv:regionCountry' => 'String',
            'ym:pv:screenColors' => 'UInt8',
            'ym:pv:screenFormat' => 'UInt16',
            'ym:pv:screenHeight' => 'UInt16',
            'ym:pv:screenOrientation' => 'String',
            'ym:pv:screenWidth' => 'UInt16',
            'ym:pv:windowClientHeight' => 'UInt16',
            'ym:pv:windowClientWidth' => 'UInt16',
            'ym:pv:params' => 'String',
            'ym:pv:lastTrafficSource' => 'String',
            'ym:pv:lastSearchEngine' => 'String',
            'ym:pv:lastSearchEngineRoot' => 'String',
            'ym:pv:lastAdvEngine' => 'String',
            'ym:pv:artificial' => 'UInt8',
            'ym:pv:pageCharset' => 'String',
            'ym:pv:link' => 'UInt8',
            'ym:pv:download' => 'UInt8',
            'ym:pv:notBounce' => 'UInt8',
            'ym:pv:lastSocialNetwork' => 'String',
            'ym:pv:httpError' => 'String',
            'ym:pv:clientID' => 'UInt64',
            'ym:pv:networkType' => 'String',
            'ym:pv:lastSocialNetworkProfile' => 'String',
            'ym:pv:goalsID' => 'Array(UInt32)',
            'ym:pv:shareService' => 'String',
            'ym:pv:shareURL' => 'String',
            'ym:pv:shareTitle' => 'String',
            'ym:pv:iFrame' => 'UInt8',
            'ym:pv:date' => 'Date',
            'ym:pv:GCLID' => 'String',
            'ym:pv:regionCityID' => 'UInt32',
            'ym:pv:regionCountryID' => 'UInt32',
        ],
        'visits' => [
            'ym:s:counterID' => 'UInt32',
            'ym:s:watchIDs' => 'Array(UInt64)',
            'ym:s:dateTime' => 'DateTime',
            'ym:s:dateTimeUTC' => 'DateTime',
            'ym:s:isNewUser' => 'UInt8',
            'ym:s:startURL' => 'String',
            'ym:s:endURL' => 'String',
            'ym:s:pageViews' => 'Int32',
            'ym:s:visitDuration' => 'UInt32',
            'ym:s:bounce' => 'UInt8',
            'ym:s:ipAddress' => 'String',
            'ym:s:params' => 'String',
            'ym:s:goalsID' => 'Array(UInt32)',
            'ym:s:goalsSerialNumber' => 'Array(UInt32)',
            'ym:s:goalsDateTime' => 'Array(DateTime)',
            'ym:s:goalsPrice' => 'Array(Int64)',
            'ym:s:goalsOrder' => 'Array(String)',
            'ym:s:goalsCurrency' => 'Array(String)',
            'ym:s:clientID' => 'UInt64',
            'ym:s:lastTrafficSource' => 'String',
            'ym:s:lastAdvEngine' => 'String',
            'ym:s:lastReferalSource' => 'String',
            'ym:s:lastSearchEngineRoot' => 'String',
            'ym:s:lastSearchEngine' => 'String',
            'ym:s:lastSocialNetwork' => 'String',
            'ym:s:lastSocialNetworkProfile' => 'String',
            'ym:s:referer' => 'String',
            'ym:s:lastDirectClickOrder' => 'String',
            'ym:s:lastDirectBannerGroup' => 'String',
            'ym:s:lastDirectClickBanner' => 'String',
            'ym:s:lastDirectPhraseOrCond' => 'String',
            'ym:s:lastDirectPlatformType' => 'String',
            'ym:s:lastDirectPlatform' => 'String',
            'ym:s:lastDirectConditionType' => 'String',
            'ym:s:lastCurrencyID' => 'String',
            'ym:s:from' => 'String',
            'ym:s:UTMCampaign' => 'String',
            'ym:s:UTMContent' => 'String',
            'ym:s:UTMMedium' => 'String',
            'ym:s:UTMSource' => 'String',
            'ym:s:UTMTerm' => 'String',
            'ym:s:openstatAd' => 'String',
            'ym:s:openstatCampaign' => 'String',
            'ym:s:openstatService' => 'String',
            'ym:s:openstatSource' => 'String',
            'ym:s:hasGCLID' => 'UInt8',
            'ym:s:regionCountry' => 'String',
            'ym:s:regionCity' => 'String',
            'ym:s:browserLanguage' => 'String',
            'ym:s:browserCountry' => 'String',
            'ym:s:clientTimeZone' => 'Int16',
            'ym:s:deviceCategory' => 'String',
            'ym:s:mobilePhone' => 'String',
            'ym:s:mobilePhoneModel' => 'String',
            'ym:s:operatingSystemRoot' => 'String',
            'ym:s:operatingSystem' => 'String',
            'ym:s:browser' => 'String',
            'ym:s:browserMajorVersion' => 'UInt16',
            'ym:s:browserMinorVersion' => 'UInt16',
            'ym:s:browserEngine' => 'String',
            'ym:s:browserEngineVersion1' => 'UInt16',
            'ym:s:browserEngineVersion2' => 'UInt16',
            'ym:s:browserEngineVersion3' => 'UInt16',
            'ym:s:browserEngineVersion4' => 'UInt16',
            'ym:s:cookieEnabled' => 'UInt8',
            'ym:s:javascriptEnabled' => 'UInt8',
            'ym:s:flashMajor' => 'UInt8',
            'ym:s:flashMinor' => 'UInt8',
            'ym:s:screenFormat' => 'UInt16',
            'ym:s:screenColors' => 'UInt8',
            'ym:s:screenOrientation' => 'String',
            'ym:s:screenWidth' => 'UInt16',
            'ym:s:screenHeight' => 'UInt16',
            'ym:s:physicalScreenWidth' => 'UInt16',
            'ym:s:physicalScreenHeight' => 'UInt16',
            'ym:s:windowClientWidth' => 'UInt16',
            'ym:s:windowClientHeight' => 'UInt16',
            'ym:s:purchaseID' => 'Array(String)',
            'ym:s:purchaseDateTime' => 'Array(DateTime)',
            'ym:s:purchaseAffiliation' => 'Array(String)',
            'ym:s:purchaseRevenue' => 'Array(Float64)',
            'ym:s:purchaseTax' => 'Array(Float64)',
            'ym:s:purchaseShipping' => 'Array(Float64)',
            'ym:s:purchaseCoupon' => 'Array(String)',
            'ym:s:purchaseCurrency' => 'Array(String)',
            'ym:s:purchaseProductQuantity' => 'Array(Int64)',
            'ym:s:productsPurchaseID' => 'Array(String)',
            'ym:s:productsID' => 'Array(String)',
            'ym:s:productsName' => 'Array(String)',
            'ym:s:productsBrand' => 'Array(String)',
            'ym:s:productsCategory' => 'Array(String)',
            'ym:s:productsCategory1' => 'Array(String)',
            'ym:s:productsCategory2' => 'Array(String)',
            'ym:s:productsCategory3' => 'Array(String)',
            'ym:s:productsCategory4' => 'Array(String)',
            'ym:s:productsCategory5' => 'Array(String)',
            'ym:s:productsVariant' => 'Array(String)',
            'ym:s:productsPosition' => 'Array(Int32)',
            'ym:s:productsPrice' => 'Array(Float64)',
            'ym:s:productsCurrency' => 'Array(String)',
            'ym:s:productsCoupon' => 'Array(String)',
            'ym:s:productsQuantity' => 'Array(Int64)',
            'ym:s:impressionsURL' => 'Array(String)',
            'ym:s:impressionsDateTime' => 'Array(DateTime)',
            'ym:s:impressionsProductID' => 'Array(String)',
            'ym:s:impressionsProductName' => 'Array(String)',
            'ym:s:impressionsProductBrand' => 'Array(String)',
            'ym:s:impressionsProductCategory' => 'Array(String)',
            'ym:s:impressionsProductCategory1' => 'Array(String)',
            'ym:s:impressionsProductCategory2' => 'Array(String)',
            'ym:s:impressionsProductCategory3' => 'Array(String)',
            'ym:s:impressionsProductCategory4' => 'Array(String)',
            'ym:s:impressionsProductCategory5' => 'Array(String)',
            'ym:s:impressionsProductVariant' => 'Array(String)',
            'ym:s:impressionsProductPrice' => 'Array(Int64)',
            'ym:s:impressionsProductCurrency' => 'Array(String)',
            'ym:s:impressionsProductCoupon' => 'Array(String)',
            'ym:s:lastDirectClickOrderName' => 'String',
            'ym:s:lastClickBannerGroupName' => 'String',
            'ym:s:lastDirectClickBannerName' => 'String',
            'ym:s:networkType' => 'String',
            'ym:s:visitID' => 'UInt64',
            'ym:s:date' => 'Date',
            'ym:s:regionCountryID' => 'UInt32',
            'ym:s:regionCityID' => 'UInt32',
            'ym:s:lastGCLID' => 'String',
            'ym:s:firstGCLID' => 'String',
            'ym:s:lastSignificantGCLID' => 'String',
            'ym:s:offlineCallTalkDuration' => 'Array(Int32)',
            'ym:s:offlineCallHoldDuration' => 'Array(Int32)',
            'ym:s:offlineCallMissed' => 'Array(Int32)',
            'ym:s:offlineCallTag' => 'Array(String)',
            'ym:s:offlineCallFirstTimeCaller' => 'Array(Int32)',
            'ym:s:offlineCallURL' => 'Array(String)',
        ],
    ];

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

                $token = (string)($channel->yandex_token ?? '');
                $counter = (string)($channel->yandex_counter ?? '');

                $this->logger->debug("Источник {$channel->name}.", compact('token', 'counter'));

                if ($token == '' || $counter == '') {
                    return true;
                }


                $db = $this->dbClient;

                $dbName = "db_{$counter}_ya_counter";

                //region Создание базы данных
                if (!$db->isDatabaseExist($dbName)) {
                    $db->write("CREATE DATABASE {$dbName}");
                }
                //endregion

                $db->database($dbName);

                foreach ($this->fields as $tableName => $fields) {

                    $this->logger->debug("Источник {$channel->name}.", compact('dbName', 'tableName'));

                    //region Создание таблицы в базе данных

                    if (!$db->isExists($dbName, $tableName)) {

                        $sqlFields = [];

                        foreach ($fields as $fieldName => $fieldType) {
                            $fieldName = str_replace('ym:pv:', '', $fieldName);
                            $fieldName = str_replace('ym:s:', '', $fieldName);
                            $fieldName = ucfirst($fieldName);
                            $sqlFields[] = "{$fieldName} {$fieldType}";
                        }

                        $sqlFields = implode(', ', $sqlFields);

                        $sql = "CREATE TABLE {$dbName}.{$tableName} (
                                    {$sqlFields}
                                ) ENGINE = MergeTree(Date, intHash32(ClientID), (Date, intHash32(ClientID)), 8192)";

                        $db->write($sql);
                    }
                    //endregion

                    //region Вычисление необходимости загрузки данных и соответствующего диапазона дат

                    $startDate = $this->getChannelCounterCreationDate($channel);
                    $startDate->setTime(0, 0);
                    $endDate = new \DateTime('yesterday');
                    $endDate->setTime(23, 59, 59, 999);

                    $needData = false;

                    $this->logger->debug("Начальные даты.", compact('startDate', 'endDate'));

                    while ($startDate->getTimestamp() < $endDate->getTimestamp()) {
                        if ($this->isDataPresent($startDate, $endDate, $dbName, $tableName)) {
                            $startDate->add(new \DateInterval('P1D'));
                            continue;
                        }

                        $needData = true;
                        break;
                    }

                    $this->logger->debug("Необходимые даты.", compact('startDate', 'endDate', 'needData'));

                    //endregion

                    if (!$needData) {
                        continue;
                    }

                    //region Инициализация клиента API

                    $client = new MetrikaClient($token);
//                    $goProxyClient = new GoProxyClient();
//                    $goProxyClient->setProxyUrl($channel->go_proxy_url);
//                    $client->setHttpClient($goProxyClient);

                    //endregion

                    try {

                        //region Очистка необработанных запросов API

                        $request = new LogListRequest($counter);
                        $response = $client->sendLogListRequest($request);

                        /**
                         * @var LogRequest $logRequest
                         */
                        foreach ($response->getRequests() as $logRequest) {
                            $this->logger->debug(
                                "Неочищенные логи.",
                                ['logRequest' => ['Id' => $logRequest->getId(), 'Status' => $logRequest->getStatus()]]
                            );

                            switch ($logRequest->getStatus()) {
                                case 'processed':
                                    $request = (new CleanRequest($counter, $logRequest->getId()));
                                    $client->sendCleanRequest($request);
                                    break;
                                default:
                                    $request = (new CancelRequest($counter, $logRequest->getId()));
                                    $client->sendCancelRequest($request);
                                    break;
                            }

                        }

                        //endregion

                        //region Запрос к API о возможности запроса

                        $request = (new CapabilityRequest($counter))
                            ->setDate1($startDate)
                            ->setDate2($endDate)
                            ->setFields(array_keys($fields))
                            ->setSource($tableName);

                        $response = $client->sendCapabilityRequest($request);

                        if (!empty($response->getErrors())) {
                            continue;
                        }

                        $logRequestEvaluation = $response->getLogRequestEvaluation();

                        $this->logger->debug(
                            "Возможность создания логов.",
                            [
                                'logRequestEvaluation' => [
                                    'possible' => $logRequestEvaluation->isPossible(),
                                    'maxDays' => $logRequestEvaluation->getMaxPossibleDayQuantity(),
                                ],
                            ]
                        );

                        //endregion

                        if (!$logRequestEvaluation->isPossible()) {
                            continue;
                        }

                        //region Проверка интервала дат в сравнении с показателем из запроса и изменение интервала при необходимости

                        $maxDays = $logRequestEvaluation->getMaxPossibleDayQuantity();

                        $deltaDays = ($endDate->diff($startDate)->days + 1);

                        if ($deltaDays > $maxDays) {
                            $addDays = $deltaDays - $maxDays;
                            $startDate->add(new \DateInterval("P{$addDays}D"));
                        }

                        $this->logger->debug("Итоговые даты.", compact('startDate', 'endDate'));

                        //endregion

                        //region Создание запроса логов

                        $request = (new CreateRequest($counter))
                            ->setDate1($startDate)
                            ->setDate2($endDate)
                            ->setFields(array_keys($fields))
                            ->setSource($tableName);

                        $response = $client->sendCreateRequest($request);

                        if (!empty($response->getErrors())) {
                            continue;
                        }

                        //endregion

                        //region Ожидание завершения обработки запроса

                        $logRequest = $response->getLogRequest();
                        $requestId = $logRequest->getId();
                        $requestStatus = $logRequest->getStatus();

                        $this->logger->debug(
                            "Создание запроса.",
                            ['logRequest' => ['Id' => $logRequest->getId(), 'Status' => $logRequest->getStatus()]]
                        );

                        $requestCreationTime = new \DateTime();

                        while (true || $requestCreationTime->diff(new \DateTime())->i < 10) {
                            switch ($requestStatus) {
                                case 'processed':
                                case 'canceled':
                                case 'processing_failed':
                                    break 2;
                            }

                            sleep(120);

                            $request = (new InformationRequest($counter, $requestId));
                            $response = $client->sendInformationRequest($request);

                            if (!empty($response->getErrors())) {
                                throw new \Exception($response->getErrorMessage(), $response->getErrorCode());
                            }

                            $logRequest = $response->getLogRequest();
                            $requestStatus = $logRequest->getStatus();

                            $this->logger->debug(
                                "Проверка статуса запроса.",
                                ['logRequest' => ['Id' => $logRequest->getId(), 'Status' => $logRequest->getStatus()]]
                            );
                        }

                        //endregion

                        //region Сохранение результатов в таблице базы данных

                        if ($requestStatus == 'processed') {

                            /**
                             * @var LogRequestPart $part
                             */
                            foreach ($logRequest->getParts() as $part) {

                                try {
                                    $request = new DownloadRequest($counter, $requestId, $part->getNumber());
                                    $response = $client->sendDownloadRequest($request);
                                } catch (RequestException $e) {
                                    $client = new MetrikaClient($token);
                                    $request = new DownloadRequest($counter, $requestId, $part->getNumber());
                                    $response = $client->sendDownloadRequest($request);
                                }



                                if ($response instanceof \GuzzleHttp\Psr7\Stream) {

                                    $fileName = "{$dbName}-{$tableName}.tsv";

                                    if (\Storage::exists($fileName)) {
                                        \Storage::delete($fileName);
                                    }

                                    $this->logger->debug(
                                        "Получение результатов.",
                                        [
                                            'logRequest' => [
                                                'Id' => $logRequest->getId(),
                                                'Status' => $logRequest->getStatus(),
                                            ],
                                            'Part' => ['Number' => $part->getNumber(), 'Size' => $part->getSize()],
                                        ]
                                    );

                                    $size = $part->getSize();

                                    $content = $response->read($size);
                                    $content = str_replace("\'", "'", $content);

                                    $content = explode("\n", $content);

                                    $numColumns = isset($content[0]) ? count(explode("\t", $content[0])) : 0;

                                    foreach ($content as $key => &$row) {
                                        if (count(explode("\t", $row)) != $numColumns) {
                                            unset($content[$key]);
                                        }
                                    }

                                    $content = implode("\n", $content);

                                    \Storage::append($fileName, $content);


                                    $this->logger->debug(
                                        "Получены результаты.",
                                        [
                                            'Size' => $size,
                                            'Meta' => $response->getMetadata(),
                                        ]
                                    );

                                    $sqlFields = [];

                                    foreach ($fields as $fieldName => $fieldType) {
                                        $fieldName = str_replace('ym:pv:', '', $fieldName);
                                        $fieldName = str_replace('ym:s:', '', $fieldName);
                                        $fieldName = ucfirst($fieldName);
                                        $sqlFields[] = $fieldName;
                                    }

                                    $db->insertBatchFiles(
                                        $tableName,
                                        \Storage::path($fileName),
                                        $sqlFields,
                                        'TabSeparatedWithNames'
                                    );
                                }

                            }

                        }

                        //endregion

                        //region Очистка запроса

                        $this->logger->debug(
                            "Очистка запроса.",
                            ['logRequest' => ['Id' => $logRequest->getId(), 'Status' => $logRequest->getStatus()]]
                        );

                        $request = (new CleanRequest($counter, $requestId));
                        $client->sendCleanRequest($request);

                        //endregion

                    } catch (\Exception $e) {
                        $this->logger->debug($e->getMessage(), $e->getTrace());
                    }

                }

                return true;
            }
        );
    }

    /**
     * Проверка наличия данных по временному диапазону в базе данных
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param string $dbName
     * @param string $tableName
     * @return bool
     */
    protected function isDataPresent(\DateTime $startDate, \DateTime $endDate, string $dbName, string $tableName): bool
    {
        $db = $this->dbClient;

        if (!$db->isDatabaseExist($dbName) || !$db->isExists($dbName, $tableName)) {
            return false;
        }

        $startDate = $startDate->format("Y-m-d");
        $endDate = $endDate->format("Y-m-d");

        $sql = "SELECT count() as num FROM {$dbName}.{$tableName} WHERE Date >= '{$startDate}' AND Date <= '{$endDate}'";

        $result = $db->select($sql)->fetchOne('num');

        return (bool)(int)$result;
    }


    /**
     * Получение даты создания счетчика источника
     *
     * @param Channel $channel
     * @return \DateTime|null
     * @throws \Exception
     */
    protected function getChannelCounterCreationDate(Channel $channel)
    {

        $token = (string)($channel->yandex_token ?? '');
        $counter = (string)($channel->yandex_counter ?? '');
        $proxyUrl = (string)($channel->go_proxy_url ?? null);

        if ($token == '' || $counter == '') {
            return null;
        }

        $response = new Unirest\Response('500', '', '');

        try {

            $url = "https://api-metrika.yandex.ru/management/v1/counter/{$counter}";

            $headers = [
                'Authorization' => "OAuth {$token}",
            ];

            if (is_null($proxyUrl)) {
                $headers['Go'] = $url;
                $url = $proxyUrl;
            }

            $response = Unirest\Request::get($url, $headers);

        } catch (\Exception $exception) {
        }

        if ($response->code != 200) {
            return null;
        }

        return new \DateTime($response->body->counter->create_time);
    }
}
