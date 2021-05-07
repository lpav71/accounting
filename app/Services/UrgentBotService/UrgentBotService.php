<?php

namespace App\Services\UrgentBotService;

use App;
use App\Configuration;

class UrgentBotService
{
    /**
     * @var false|\Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
     */
    private $number;

    /**
     * @var bool|mixed|string
     */
    private $zvonokApiKey;

    /**
     * @var false|\Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
     */
    private $zvonokCampaignId;

    /**
     * Api url zvonok.com
     *
     * @var string
     */
    private $callServiceUrl;

    /**
     * Сообщение которое будет передано ботом
     *
     * @var string
     */
    private $message;

    /**
     * UrgentBotService constructor.
     */
    public function __construct()
    {
        $this->number = Configuration::where(['name' => 'Al_urgent_number'])->first()->values;
        $this->zvonokApiKey = config('urgent-call.public_key');
        $this->zvonokCampaignId = config('urgent-call.campaign_id');
        $this->callServiceUrl = config('urgent-call.api_url');
    }

    /**
     * 1 звонок
     */
    public function makeNeedCall()
    {
        $this->message = 'Не+могу+дальше+работать+без+ответа,+нужно+выйти+на+связь';
        $url = "$this->callServiceUrl?public_key=$this->zvonokApiKey&phone=$this->number&campaign_id=$this->zvonokCampaignId&text=$this->message";
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $out = curl_exec($curl);
            curl_close($curl);
        }
    }

    /**
     * 3 вызова
     */
    public function makeCriticalCall()
    {
        $this->message = 'Срочная+связь,+критическая+ситуация';
        $url = "$this->callServiceUrl?public_key=$this->zvonokApiKey&phone=$this->number&campaign_id=$this->zvonokCampaignId&text=$this->message";
        for ($i = 0; $i < 3; $i++) {
            if ($curl = curl_init()) {
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $out = curl_exec($curl);
                sleep(5);
                curl_close($curl);
            }
        }
    }
}