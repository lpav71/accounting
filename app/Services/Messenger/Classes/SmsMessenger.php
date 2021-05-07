<?php
declare(strict_types=1);

namespace App\Services\Messenger\Classes;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * SMS messenger for https://iqsms.ru/api/api_json/
 *
 * needed minimal sms settings
 *
 * Class SmsMessenger
 * @package App\Services\Messenger\Classes
 */
/*
    minimal needed sms settings in Channel messenger_settings
    {
        "sms":{
            "login":"login",
            "password":"password",
            "mts_sign":"SMS DUCKOHT",
            "sign":"MediaGramma"
        }
    }
*/

class SmsMessenger extends AbstractMessenger
{

    private $httpClient = null;
    
    public $error_message = "Ошибка";

    public function __construct()
    {
        $this->httpClient = new Client([
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => 'applicatipon/json',
                'charset' => 'utf-8'
            ]
        ]);
    }

    public static function getType(): string
    {
        return 'sms';
    }

    function send(): bool
    {
        
        $res = $this->httpClient->request('POST', 'https://api.iqsms.ru/messages/v2/send.json', [
            'body' => json_encode(
                [
                    "messages" => [
                        'phone' => $this->getDestination(),
                        'text' => $this->getMessageToSend(),
                        "clientId" => "0",
                        'sender' => $this->getSign()
                    ],
                    'login' => $this->getLogin(),
                    'password' => $this->getPassword(),
                ],
                JSON_UNESCAPED_UNICODE
            )
        ]);        
        if (!preg_match('/accepted/', (string)$res->getBody())) {
            if(isset(json_decode((string)$res->getBody())->messages[0]->status)){
                $this->error_message = json_decode((string)$res->getBody())->messages[0]->status;
            }
            return false;
        }
        return parent::send();
    }

    /**
     * get message which ready to sending
     *
     * @return string
     */
    private function getMessageToSend(): string
    {
        return $this->getMessage();
    }

    /**
     * get SMS service sign
     *
     * @return string|null
     */
    private function getSign(): ?string
    {
        if ($this->isMts()) {
            return json_decode($this->getSender()->messenger_settings)->sms->mts_sign;
        }
        return json_decode($this->getSender()->messenger_settings)->sms->sign;
    }

    /**
     * is MTS customer`s operator
     *
     * @return bool
     */
    private function isMts(): bool
    {
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
            "http" =>array(
                'timeout' => 60
            )
        ); 
        $link = "https://zniis.ru/bdpn/check/?num=".substr($this->getDestination(),1);
        $html = file_get_contents($link, false, stream_context_create($arrContextOptions));
        if($html !== false){
            $crawler = new Crawler(null, $link);
            $crawler->addHtmlContent($html, 'UTF-8');
            $body = $crawler->filter('body')->text();            
            return (bool) preg_match('/Мобильные ТелеСистемы/', $body);
        }else{           
            return true;
        }            
    }

    /**
     * get SMS service login
     *
     * @return string
     */
    public function getLogin(): string
    {
        return json_decode($this->getSender()->messenger_settings)->sms->login;
    }

    /**
     * get SMS service password
     *
     * @return string
     */
    private function getPassword(): string
    {
        return json_decode($this->getSender()->messenger_settings)->sms->password;
    }


    public function getBalance(): float
    {

        $res = $this->httpClient->request('POST', 'https://api.iqsms.ru/messages/v2/balance.json', [
            'body' => json_encode(
                [
                    'login' => $this->getLogin(),
                    'password' => $this->getPassword(),
                ],
                JSON_UNESCAPED_UNICODE
            )
        ]);
        return (float)json_decode((string)$res->getBody())->balance[0]->balance;

    }
}