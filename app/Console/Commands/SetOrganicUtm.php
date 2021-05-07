<?php

namespace App\Console\Commands;

use App\Order;
use App\Utm;
use App\UtmCampaign;
use App\UtmSource;
use Illuminate\Console\Command;
use Unirest;

class SetOrganicUtm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:organic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set organic utm to orders';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Создаем новые utm campaigns и utm source к которым будем привязывать заказы
        $utmCompaignOrganic = UtmCampaign::firstOrCreate(['name' => 'organic']);
        $utmSourceOrganic = UtmSource::firstOrCreate(['name' => 'organic']);

        //Берем все заказы которые имеют utm no
        $orders = Order::all()->filter(function (Order $order) {
            return !$order->utm_campaign && !$order->search_query && $order->clientID && $order->channel->go_proxy_url && $order->channel->yandex_counter;
        });

        //И проверяем каждый на наличие органики
        foreach ($orders as $order) {
            $headers = [];
            $headers['Authorization'] = 'OAuth ' . $order->channel->yandex_token;
            $url = 'https://api-metrika.yandex.ru/stat/v1/data?ids=' . $order->channel->yandex_counter . '&date1=2018-11-06&filters=ym:s:clientID==' . $order->clientID . '&metrics=ym:s:visits&dimensions=ym:s:clientID,ym:s:trafficSource,ym:s:UTMCampaign,ym:s:UTMSource,ym:s:UTMTerm&limit=10000';
            if (!is_null($order->channel->go_proxy_url)) {
                $headers['Go'] = $url;
                $url = $order->channel->go_proxy_url;
            }

            $response = Unirest\Request::get($url, $headers);

            //Если найден такой clientId в источнике
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
                    try {
                        $order->utm()->associate(Utm::firstOrCreate(
                            [
                                'utm_campaign_id' =>
                                    $utmCompaignOrganic->id,
                                'utm_source_id' =>
                                    $utmSourceOrganic->id,
                            ]
                        ));
                        $order->save();
                    } catch (\Exception $e) {
                        dump($e->getMessage());
                        dump($order->utm_campaign);
                        dump($order->utm_source);
                        dump($order->id);
                        dump($order->utm);
                        dump($order->utm_id);
                        die();
                    }
                }
            }
        }
    }
}
