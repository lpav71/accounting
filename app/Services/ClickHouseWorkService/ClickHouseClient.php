<?php

namespace App\Services\ClickHouseWorkService;

use GuzzleHttp\Client;

class ClickHouseClient
{
    /**
     * @return Client
     */
    public function createClient() : Client
    {
        $client = new Client([
            'headers' => [
                'content-type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
                'charset' => 'utf-8',
                'Authorization' => 'Bearer ' . config('counter.bearer')
            ]
        ]);

        return $client;
    }
}