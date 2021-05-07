<?php


namespace App\Services\Channel;

use App\Http\Resources\PrestaProductAvailabilityCollectionJsonApi;
use App\Http\Resources\PrestaProductAvailabilityJsonApi;
use App\Http\Resources\PrestaProductCollectionJsonApi;
use App\Http\Resources\PrestaProductPriceCollectionJsonApi;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

/**
 * Class UploadProductService
 * @package App\Services\Channel
 */
class UploadProductService
{

    /**
     * @param Collection $collection
     * @return bool
     */
    public static function upload(Collection $collection)
    {
        $uri = $collection->first()->channel->upload_address;
        $key = $collection->first()->channel->upload_key;
        if(empty($uri) || empty($key)){
            return false;
        }
        $body = (new PrestaProductCollectionJsonApi($collection))->toResponse(app('request'))->getContent();
        return self::send($uri,$key,$body);
    }

    public static function uploadPrices(Collection $collection)
    {
        $uri = $collection->first()->channel->upload_address_price;
        $key = $collection->first()->channel->upload_key;
        if(empty($uri) || empty($key)){
            return false;
        }
        $body = (new PrestaProductPriceCollectionJsonApi($collection))->toResponse(app('request'))->getContent();
        return self::send($uri,$key,$body);
    }


    public static function uploadAvailability(Collection $collection)
    {
        $uri = $collection->first()->channel->upload_address_availability;
        $key = $collection->first()->channel->upload_key;
        if(empty($uri) || empty($key)){
            return false;
        }
        $body = (new PrestaProductAvailabilityCollectionJsonApi($collection))->toResponse(app('request'))->getContent();
        return self::send($uri,$key,$body);
    }

    private static function send(string $uri, string $key, $body)
    {
        $client = new Client();
        try {
            $response = $client->request('POST',
                $uri
                ,
                [
                    'headers' => [
                        'Content-Type' => 'application/vnd.api+json',
                        'Authorization' => 'Bearer ' . $key
                    ],
                    'body' => $body
                ]);
        } catch (GuzzleException $e) {
            \Log::error($e->getMessage());
        }
        return true;
    }
}