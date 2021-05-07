<?php


namespace App\Services\ThermalPrinter;


use App\Order;
use App\ProductExchange;
use Carbon\Carbon;
use GuzzleHttp\Client;

class ThermalPrinter
{

    public static function print(Order $order):void
    {
        if(empty($order->channel->check_title) && empty($order->channel->check_title_2)){
            return;
        }
        foreach ($order->orderDetails->groupBy('printing_group') as $printingGroup => $orderDetails) {
            $body = [
                'data' => [
                    'type' => 'check',
                    'attributes' => [
                        'title' => sprintf('%s', $order->channel->check_title),
                        'title_2' => sprintf('%s', $order->channel->check_title_2),
                        'user' => $order->channel->check_user,
                        'inn' => $order->channel->inn,
                        'ogrn' => $order->channel->ogrn,
                        'kpp' => $order->channel->kpp,
                        'qrcode' => $order->channel->check_qr_code,
                        'shiftNumber' => $order->getOrderNumber(),
                        'number' => $printingGroup + 1,
                        'dateTime' => Carbon::now()->format('d.m.Y H:i'),
                        'place' => $order->channel->check_place,
                    ],
                    'relationships' => [
                        'products' => [
                            'data' => []
                        ]
                    ]
                ]
            ];
            foreach ($orderDetails as $orderDetail) {
                $product = [];
                $product['type'] = 'orderDetail';
                $product['id'] = $orderDetail->id;
                $body['data']['relationships']['products']['data'][] = $product;
            }
            foreach ($orderDetails as $orderDetail) {
                $product = [];
                $product['type'] = 'orderDetail';
                $product['id'] = $orderDetail->id;
                $attributes = [
                    'name' => $orderDetail->product->name,
                    'amount' => 1,
                    'price' => $orderDetail->price
                ];
                $product['attributes'] = $attributes;
                $body['included'][] = $product;
            }
            $client = new Client([
                'base_uri' => config('thermal-printer.url') . ':' . config('thermal-printer.port'),
            ]);
            $response = $client->request('POST',
                '/print'
                ,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . config('thermal-printer.token'),
                        'Content-Type' => 'application/vnd.api+json'
                    ],
                    'json' => $body
                ]);
        }
    }

    public static function printExchange(ProductExchange $productExchange)
    {
        if(empty($productExchange->order->channel->check_title) && empty($productExchange->order->channel->check_title_2)){
            return;
        }
        foreach ($productExchange->orderDetails->groupBy('printing_group') as $printingGroup => $orderDetails) {
            $body = [
                'data' => [
                    'type' => 'check',
                    'attributes' => [
                        'title' => sprintf('%s', $productExchange->order->channel->check_title),
                        'title_2' => sprintf('%s', $productExchange->order->channel->check_title_2),
                        'user' => $productExchange->order->channel->check_user,
                        'inn' => $productExchange->order->channel->inn,
                        'ogrn' => $productExchange->order->channel->ogrn,
                        'kpp' => $productExchange->order->channel->kpp,
                        'qrcode' => $productExchange->order->channel->check_qr_code,
                        'shiftNumber' => $productExchange->id,
                        'number' => $printingGroup + 1,
                        'dateTime' => Carbon::now()->format('d.m.Y H:i'),
                        'place' => $productExchange->order->channel->check_place,
                    ],
                    'relationships' => [
                        'products' => [
                            'data' => []
                        ]
                    ]
                ]
            ];
            foreach ($orderDetails as $orderDetail) {
                $product = [];
                $product['type'] = 'orderDetail';
                $product['id'] = $orderDetail->id;
                $body['data']['relationships']['products']['data'][] = $product;
            }
            foreach ($orderDetails as $orderDetail) {
                $product = [];
                $product['type'] = 'orderDetail';
                $product['id'] = $orderDetail->id;
                $attributes = [
                    'name' => $orderDetail->product->name,
                    'amount' => 1,
                    'price' => $orderDetail->price
                ];
                $product['attributes'] = $attributes;
                $body['included'][] = $product;
            }
            $client = new Client([
                'base_uri' => config('thermal-printer.url') . ':' . config('thermal-printer.port'),
            ]);
            $response = $client->request('POST',
                '/print'
                ,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . config('thermal-printer.token'),
                        'Content-Type' => 'application/vnd.api+json'
                    ],
                    'json' => $body
                ]);
        }
    }

}