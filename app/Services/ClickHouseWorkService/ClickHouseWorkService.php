<?php
declare(strict_types=1);

namespace App\Services\ClickHouseWorkService;

use App\Order;
use App\OrderComment;
use App\Services\Telephony\Interfaces\CallInterface;
use App\Utm;
use App\UtmCampaign;
use App\UtmSource;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Log;
use Illuminate\Support\Facades\Storage;
use Unirest;

class ClickHouseWorkService
{
    /**
     * @var Client
     *
     */
    private $client;

    public function __construct()
    {
        $client = new ClickHouseClient();
        $this->client = $client->createClient();
    }

    /**
     * @param Order $order
     * @return bool|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setClientToOrder(Order $order)
    {
        //Получаем дату с которой будем сравнивать дату посещения клиента
        $firstEventDate = $this->getCmpDate($order);
        //Создаем новые utm campaigns и utm source к которым будем привязывать заказы, которые не смогли определить
        $utmCompaignUndefined = UtmCampaign::firstOrCreate(['name' => 'undefined']);
        $utmSourceUndefined = UtmSource::firstOrCreate(['name' => 'undefined']);

        //Запрос в API каунтера
        $response = $this->client->request('GET', config('counter.url') . 'api/watch', [
            'query' => [
                'type' => 'articles',
                'articles' => $order->getAllArticles(),
                'dbname' => $order->channel->db_name,
                'date' => $firstEventDate->format('Y-m-d h:m:s'),
            ],
        ]);
        $body = json_decode($response->getBody()->getContents(), true);
        if ($body !== null) {
            //Группируем визиты по пользователям
            $users = [];
            foreach ($body['data'] as $visit) {
                $users[$visit['attributes']['clientId']][] = $visit;
            }
            //Запрос в dadata для получения области по названию города
            $orderRegion = '';
            if ($oCurl = curl_init("http://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address")) {
                curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($oCurl, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Token ' . config('dadata.token')
                ]);
                curl_setopt($oCurl, CURLOPT_POST, 1);
                curl_setopt($oCurl, CURLOPT_POSTFIELDS, json_encode(['query' => $order->delivery_city, 'count' => 1]));
                $sResult = curl_exec($oCurl);
                $arResult = json_decode($sResult, true);
                curl_close($oCurl);
                if ($arResult['suggestions'] && $arResult['suggestions'][0]['data'] && $arResult['suggestions'][0]['data']['region']) {
                    $orderRegion = $arResult['suggestions'][0]['data']['region'];
                }
            }

            //Получаем utm и регион каждого клиента
            $yandexResult = $this->getYandexClientInfo($users, $order);

            if ($yandexResult) {
                foreach ($users as $key => &$user) {
                    $user['region'] = [];
                    //ищем clientId
                    $clientId = '';
                    //Определяем clientId, для сопоставление с response из метрики
                    foreach ($user[0]['relationships']['users']['data'] as $clientInfo) {
                        if (!stristr($clientInfo['clientId'], '.')) {
                            $clientId = $clientInfo['clientId'];
                        }
                    }
                    if ($clientId) {
                        foreach ($yandexResult as $visit) {
                            //Если не тот клиент то продолжаем поиск
                            if ($clientId != $visit->dimensions[0]->name) {
                                continue;
                            }
                            //utm campaign
                            if (!is_null($visit->dimensions[2]->name)) {
                                $utm['utm_campaign'] = $visit->dimensions[2]->name;
                            }

                            //utm source
                            if (!is_null($visit->dimensions[3]->name)) {
                                $utm['utm_source'] = $visit->dimensions[3]->name;
                            }

                            //utm term
                            if (!is_null($visit->dimensions[4]->name)) {
                                $utm['utm_term'] = $visit->dimensions[4]->name;
                            }

                            //По структуре запроса - 6м элементом должен лежать регион
                            if (isset($visit->dimensions[5]) && !empty($visit->dimensions[5])) {
                                if (!in_array($visit->dimensions[5]->name, $user['region'])) {
                                    $user['region'][] = $visit->dimensions[5]->name;
                                }
                            }
                        }

                        //Если по какой-то причине яндекс метрика не вернула utm, кладем utm которые были в каунтере
                        if (empty($utm['utm_campaign']) || empty($utm['utm_source'])) {
                            $utm['utm_campaign'] = empty($utm['utm_campaign']) ? $user[0]['attributes']['utm_campaign'] : $utm['utm_campaign'];
                            $utm['utm_source'] = empty($utm['utm_source']) ? $user[0]['attributes']['utm_source'] : $utm['utm_source'];
                        }
                        //Если ничего нет проверям не органика ли это
                        if (empty($utm['utm_campaign'])) {
                            try {
                                $response = $this->getYandexData(
                                    'https://api-metrika.yandex.ru/stat/v1/data?ids=' .
                                    $order->channel->yandex_counter . '&date1=2018-11-06&filters=ym:s:clientID==' . $clientId .
                                    '&metrics=ym:s:visits&dimensions=ym:s:clientID,ym:s:trafficSource,ym:s:UTMCampaign,ym:s:UTMSource,ym:s:UTMTerm&limit=10000',
                                    $order);

                                if (!empty($response->body->data)) {
                                    $organic = false;
                                    //Смотрим список визитов, если имеется реклама (ad), то не учитываем
                                    foreach ($response->body->data as $visit) {
                                        if ($visit->dimensions[1]->id == 'ad') {
                                            $organic = false;
                                            break;
                                        }

                                        if ($visit->dimensions[1]->id == 'organic') {
                                            $organic = true;
                                        }
                                    }
                                    if ($organic) {
                                        $utm['utm_campaign'] = 'organic';
                                        $utm['utm_source'] = 'organic';
                                    }
                                }
                            } catch (\Exception $exception) {
                                Log::warning(__('Order') . $order->id . 'Error :' . $exception->getMessage());
                            }
                        }
                        $user['utm'] = $utm;
                    }
                }
                //Далее отсекаем пользователей регион которых не совпадает с городом доставки заказа
                foreach ($users as $key => $value) {
                    if (isset($value['region']) && !empty($value['region'])) {
                        foreach ($value['region'] as $city) {
                            if ($city !== null) {
                                if (stripos($city, $orderRegion) === false) {
                                    unset($users[$key]);
                                }
                            } else {
                                unset($users[$key]);
                            }
                        }
                    } else {
                        unset($users[$key]);
                    }
                }

                if (count($users)) {
                    $needUser = [];
                    if (count($users) > 1) {
                        //Если подходит несколько клиентов, то выбираем у кого дата посещения ближе всего
                        $differenceInSeconds = 0;
                        foreach ($users as $user) {
                            $i = 0;
                            while (true) {
                                if (isset($user[$i])) {
                                    //Генерируем Carbon, для более точного сравнения
                                    $tmp = explode(' ', $user[$i]['attributes']['dateTime']);
                                    $date = explode('-', $tmp[0]);
                                    $time = explode(':', $tmp[1]);
                                    $dateTime = Carbon::create($date[0], $date[1], $date[2], $time[0], $time[1], $time[2]);
                                    if ($firstEventDate->diffInSeconds($dateTime) > 0) {
                                        if (empty($differenceInSeconds)) {
                                            $differenceInSeconds = $firstEventDate->diffInSeconds($dateTime);
                                            $needUser = $user;
                                        } else {
                                            if ($differenceInSeconds > $firstEventDate->diffInSeconds($dateTime)) {
                                                $differenceInSeconds = $firstEventDate->diffInSeconds($dateTime);
                                                $needUser = $user;
                                            }
                                        }
                                    }
                                } else {
                                    break;
                                }
                                $i++;
                            }
                        }
                    } else {
                        //Если подходит 1 клиент его данный и подставляем к заказу
                        $needUser = array_shift($users);
                    }
                    $order->search_query = isset($needUser['utm']['utm_term']) ? $needUser['utm']['utm_term'] : null;
                    $tmpUtmCampaign = UtmCampaign::firstOrCreate(['name' => $needUser['utm']['utm_campaign']]);
                    $tmpUtmSource = UtmSource::firstOrCreate(['name' => $needUser['utm']['utm_source']]);
                    $order->utm()->associate(Utm::firstOrCreate(
                        [
                            'utm_campaign_id' =>
                                $tmpUtmCampaign->id,
                            'utm_source_id' =>
                                $tmpUtmSource->id,
                        ]
                    ));

                    //Если каунтер вернул также gaClientId заполняем и его
                    foreach ($needUser[0]['relationships']['users']['data'] as $clientId) {
                        if (!stristr($clientId['clientId'], '.')) {
                            $order->clientID = $clientId['clientId'];
                        } else {
                            $order->gaClientID = $clientId['clientId'];
                        }
                    }
                    $order->save();
                    Log::channel('utm_no_log')->info('Клиент для заказа успешно определен, Id заказа : ' . $order->getDisplayNumber() . ' clientID = ' . $order->clientID);
                } else {
                    //Если не смогли определить нужно проставить определнный utm заказу, дабы не расходовать лимит запросов в метрике
                    $order->utm()->associate(Utm::firstOrCreate(
                        [
                            'utm_campaign_id' =>
                                $utmCompaignUndefined->id,
                            'utm_source_id' =>
                                $utmSourceUndefined->id,
                        ]
                    ));
                    $order->save();
                    Log::channel('utm_no_log')->info('Не получилось определить клиента, Id заказа : ' . $order->getDisplayNumber());
                }
            }
        } else {
            //Если в каунтере ничего нет значит заказ не получится определить
            $order->utm()->associate(Utm::firstOrCreate(
                [
                    'utm_campaign_id' =>
                        $utmCompaignUndefined->id,
                    'utm_source_id' =>
                        $utmSourceUndefined->id,
                ]
            ));
            $order->save();
            Log::channel('utm_no_log')->info('Не получилось определить клиента (не пришел ответ из каунтера), Id заказа : ' . $order->getDisplayNumber());
        }
    }

    /**
     * Получаем дату 1го события в заказе
     *
     * @param Order $order
     * @return Carbon
     */
    protected function getCmpDate(Order $order): Carbon
    {
        $orderComments = clone $order->comments;

        $orderHistory = collect([]);

        $orderHistory = $orderComments->reduce(function (Collection $result, OrderComment $orderComment) {
            $obj = new \stdClass();
            $obj->created_at = clone $orderComment->created_at;
            $obj->author = ($orderComment->user_id ? $orderComment->author->name : 'System');
            $obj->comment = $orderComment->comment;
            $obj->is_call = false;
            $obj->is_task = false;

            $matches = [];
            if (preg_match("/#task-([0-9]+)/", $orderComment->comment, $matches)) {
                $obj->is_task = true;
                $obj->task_id = (int)$matches[1];
            }

            $result->push($obj);


            return $result;
        }, $orderHistory);

        $customerCalls = $order->customer->calls();
        $orderHistory = $customerCalls->reduce(function (Collection $result, CallInterface $call) {
            $obj = new \stdClass();
            $obj->created_at = clone $call->created_at;
            $obj->author = ($call->isOutgoing() ? __('Outgoing') : __('Incoming'));
            $obj->comment = clone $call;
            $obj->is_call = true;
            $obj->is_task = false;
            $result->push($obj);

            return $result;
        }, $orderHistory);

        $orderHistory = $orderHistory->sortBy('created_at');

        return $orderHistory->first()->created_at;
    }

    /**
     * @param string $url
     * @param Order $order
     * @return array|Unirest\Response
     */
    protected function getYandexData(string $url, Order $order)
    {
        $headers = [];
        $headers['Authorization'] = 'OAuth ' . $order->channel->yandex_token;
        if (!is_null($order->channel->go_proxy_url)) {
            $headers['Go'] = $url;
            $url = $order->channel->go_proxy_url;
            $response = Unirest\Request::get($url, $headers);
        }

        return $response ? $response : [];
    }

    /**
     * Метод получения данных клиентов
     *
     * @param array $users
     * @param Order $order
     * @return array
     */
    protected function getYandexClientInfo(array $users, Order $order): array
    {
        $result = [];
        //Масксимальное кол-во значений в фильтре метрики = 100, поэтому приделтся разделять случаи где 100 и больше клиентов вернулось из каунтера
        if (count($users) <= 99) {
            //складываем clientId для оптимизации работы метрики
            $clientIds = "";
            foreach ($users as $key => $user) {
                //ищем clientId (в relations они могут хранится в случайном порядке, поэтому придется перебирать)
                $clientId = '';
                foreach ($user[0]['relationships']['users']['data'] as $clientInfo) {
                    if (!stristr($clientInfo['clientId'], '.')) {
                        $clientId = $clientInfo['clientId'];
                    }
                }
                if ($clientId) {
                    $clientIds .= "'$clientId',";
                } else {
                    //Если клиент из каунтера пришел 1 и у него нет yandex clientId - уведомление в логи
                    if (count($users) == 1) {
                        Log::channel('utm_no_log')->info('Не удалось определить клиента из-за отсутствия yandex clientId, Id заказа : ' . $order->getDisplayNumber() . ', Внутренний id клиента : ' . $key);
                    } else {
                        //Если клиент не 1 делаем также уведомление в логи для подсчета статистики
                        Log::channel('utm_no_log')->info('Отстутсвуте yandex clientId, Id заказа : ' . $order->getDisplayNumber() . ', Внутренний id клиента : ' . $key);
                    }
                }
            }
            //Удаляем последнюю запятую воизбежания бага в метрике
            $clientIds = substr($clientIds, 0, -1);
            if ($clientIds) {
                //Костыль против бага в метрике
                for ($i = 0; $i < 3; ++$i) {
                    //Берем визиты всех клиентов "whereIn (строка с clientId)"
                    $response = $this->getYandexData(
                        'https://api-metrika.yandex.ru/stat/v1/data?ids=' . $order->channel->yandex_counter .
                        '&date1=2018-11-06&filters=ym:s:clientID=.(' . $clientIds .
                        ')&metrics=ym:s:visits&dimensions=ym:s:clientID,ym:s:trafficSource,ym:s:UTMCampaign,ym:s:UTMSource,ym:s:UTMTerm,ym:s:regionArea,ym:s:dateTime&limit=10000',
                        $order);
                    if (!empty($response->body->data)) {
                        break;
                    }
                }

                $result = $response->body->data;
            }
        } else {
            //Берем по 99 clientId и делаем запрос в метрику, результаты складываем в массив
            $i = 0;
            $clientIds = "";
            foreach ($users as $key => $user) {
                //ищем clientId
                $clientId = '';
                foreach ($user[0]['relationships']['users']['data'] as $clientInfo) {
                    if (!stristr($clientInfo['clientId'], '.')) {
                        $clientId = $clientInfo['clientId'];
                    }
                }
                if ($clientId) {
                    $clientIds .= "'$clientId',";
                    $i++;
                } else {
                    Log::channel('utm_no_log')->info('Отстутсвуте yandex clientId, Id заказа : ' . $order->getDisplayNumber() . ', Внутренний id клиента : ' . $key);
                }
                if ($i == 99) {
                    $clientIds = substr($clientIds, 0, -1);
                    //Костыль против бага в метрике
                    for ($i = 0; $i < 3; ++$i) {
                        //Берем визиты всех клиентов "whereIn (строка с clientId)"
                        $response = $this->getYandexData(
                            'https://api-metrika.yandex.ru/stat/v1/data?ids=' . $order->channel->yandex_counter .
                            '&date1=2018-11-06&filters=ym:s:clientID=.(' . $clientIds .
                            ')&metrics=ym:s:visits&dimensions=ym:s:clientID,ym:s:trafficSource,ym:s:UTMCampaign,ym:s:UTMSource,ym:s:UTMTerm,ym:s:regionArea,ym:s:dateTime&limit=10000',
                            $order);
                        if (!empty($response->body->data)) {
                            break;
                        }
                    }
                    //складываем в 1 массив
                    if (!empty($response->body->data)) {
                        $result = array_merge($response->body->data, $result);
                    }
                    //Обнуляем для последующего запроса
                    $clientIds = "";
                    $i = 0;
                }
            }
            //Проверка на тот случай если последний "пак" clientId меньше 99
            if (!empty($clientIds)) {
                $clientIds = substr($clientIds, 0, -1);
                //Костыль против бага в метрике
                for ($i = 0; $i < 3; ++$i) {
                    //Берем визиты всех клиентов "whereIn (строка с clientId)"
                    $response = $this->getYandexData(
                        'https://api-metrika.yandex.ru/stat/v1/data?ids=' . $order->channel->yandex_counter .
                        '&date1=2018-11-06&filters=ym:s:clientID=.(' . $clientIds .
                        ')&metrics=ym:s:visits&dimensions=ym:s:clientID,ym:s:trafficSource,ym:s:UTMCampaign,ym:s:UTMSource,ym:s:UTMTerm,ym:s:regionArea,ym:s:dateTime&limit=10000',
                        $order);
                    if (!empty($response->body->data)) {
                        break;
                    }
                }
                if (!empty($response->body->data)) {
                    $result = array_merge($response->body->data, $result);
                }
            }
        }

        return $result;
    }
}