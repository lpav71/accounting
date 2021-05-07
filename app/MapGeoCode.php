<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\MapGeoCode
 *
 * @property int $id
 * @property string $hash
 * @property float|null $geoX
 * @property float|null $geoY
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode whereGeoX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode whereGeoY($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MapGeoCode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MapGeoCode extends Model
{
    protected $fillable = [
        'hash',
        'geoX',
        'geoY',
    ];

    /**
     * @param string $address
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function setIfEmpty(string $address) : void {
        return;
        $address = str_replace(' ', '+', $address);
        if(empty($this->geoX) || empty($this->geoY)){
            try {
                $client = new \GuzzleHttp\Client();
                $res = $client->request('GET', 'http://search.maps.sputnik.ru/search/addr?q=' . $address);
                $json = json_decode((string)$res->getBody(), true);
                if (true) {
                    $this->update(
                        [
                            'geoX' => $json['result']['address'][0]['features'][0]['geometry']['geometries'][0]['coordinates'][1],
                            'geoY' => $json['result']['address'][0]['features'][0]['geometry']['geometries'][0]['coordinates'][0],
                        ]
                    );
                }
            }catch (\Exception $e){

            }
        }
    }
}
