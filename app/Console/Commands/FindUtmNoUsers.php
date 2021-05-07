<?php

namespace App\Console\Commands;

use App\Order;
use App\Services\ClickHouseWorkService\ClickHouseWorkService;
use App\UtmCampaign;
use App\UtmSource;
use Illuminate\Console\Command;

class FindUtmNoUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'find_utm_no:clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script for schedule, which try to find orders, which haven`t utm, using counter, and (yandex metrika to determine geolocation';

    /**
     * @var ClickHouseWorkService
     */
    private $clickHouseWorkService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->clickHouseWorkService = new ClickHouseWorkService();
        parent::__construct();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        //Ищем все заказы где нет utm
        $orders = Order::all()->filter(function (Order $order) {
            return $order->utm_id == null && count($order->orderDetails) >= 1 && !is_null($order->delivery_city);
        });

        $i = 0;
        foreach ($orders as $order) {
            if ($order->channel->go_proxy_url && $order->channel->yandex_counter && $order->channel->yandex_token && $order->channel->db_name) {
                $this->clickHouseWorkService->setClientToOrder($order);
                    $i++;
            }

            //Кол-во заказов за 1 раз, нужно учитывать, что тут могут быть заказы, в случае с которыми канутер не вернет ничего
            if ($i == 2500) {
                break;
            }
        }
    }
}
