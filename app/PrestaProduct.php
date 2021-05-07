<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Unirest;
use App\Http\Resources\PrestaProduct as PrestaProductResource;
use App\Jobs\UploadProducts;
use Log;
use Illuminate\Support\Facades\Storage;

/**
 * Товар, относящийся к определенному источнику
 * 
 * App\PrestaProduct
 *
 * @property int $id
 * @property int $product_id
 * @property int $channel_id
 * @property float $price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property float|null $price_discount
 * @property string|null $description
 * @property int $is_active
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct whereOldPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PrestaProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Channel $channel
 * @property-read \App\Product $product
 * @property-read \App\Category|null $category
 * @property int $is_blocked
 * @property int $rating
 */
class PrestaProduct extends Model
{
    /**
     * fillable attributes
     *
     * @var array
     */
    protected $fillable = [
        'price',
        'product_id',
        'channel_id',
        'price_discount',
        'description',
        'is_active',
        'is_blocked',
        'rating'
    ];

    /**
     * default attribute values for new PrestaProduct object
     *
     * @var array
     */
    protected $attributes = [
        'price' => 0,
        'price_discount' => null,
        'description' => null,
        'is_active' => 0,
        'is_blocked' => 0,
        'rating' => 0
    ];


    /**
     * источник к которому относится объект
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel()
    {
        return $this->belongsTo('App\Channel');
    }

    /**
     * продкут к которому относится объект
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    /**
     *
     * Скачивает товар с источника и создаёт или обновляет в соответствии с наличием и аргументами
     * если $is_update и $update_main false то скачает только товары которых нет в базе
     *
     * @param Channel $channel  Источник для выгрузки товаров
     * @param array|string $references Артикулы товаров
     * @param bool $send_all Необходимо ли скачивать все товары
     * @param bool $is_update Необходимо ли обновлять товары каналов
     * @param bool $update_main Необходимо ли обновлять товары каналов и главный товар
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ImagickException
     */
    public static function downloadFromChannel(Channel $channel, $references = null, $send_all = false, $is_update = false, $update_main = false)
    {
        $references = is_array($references) ? $references : compact('references');
        $client = new \GuzzleHttp\Client();
        $body = [
            'upload_key' => $channel->upload_key,
            'data' => [
                'references' => $references,
                'send_all' => (bool) $send_all
            ]
        ];
        $response = $client->request('POST', $channel->download_address, [
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'headers' => ['Content-Type' => 'application/json']
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        $categories = collect($res['data']['categories']);
        $categories = $categories->mapWithKeys(function ($item, $key) {
            return [$item['id_category'] => $item['name']];
        });
        $resProducts = collect($res['data']['products']);
        foreach ($resProducts as $resProduct) {
            try{
                $category = null;
                $product = Product::firstOrNew(['reference' => $resProduct['reference']]);
                if(empty($resProduct['reference'])){
                    Log::channel('content_control_log')->info('Has no reference = '.$resProduct['name']. ' on '. $channel->name);
                }
                if($resProduct['categoryId'] != '0'){
                    //проверка на существование категории
                    if(isset($categories[$resProduct['categoryId']])){
                        $category = Category::firstOrCreate(['name' => $categories[$resProduct['categoryId']]]);
                    }else{
                        //если пришла категория которой не существует
                        $category = Category::where('is_default', 1)->first();
                    }
                    //обновление категории до более точной если товар уже существует
                    if(!$product->exists){
                        $product->category_id = $category->id;   
                    }elseif (empty($product->category_id) || $product->category->isChild($category->name)) {
                        $product->category_id = $category->id;
                        $product->save();                        
                    }
                }else{
                    Log::channel('content_control_log')->info('Has no category reference = '.$resProduct['reference']. ' on '. $channel->name);
                }
                if($product->exists && !$is_update && !$update_main) continue;
                if($update_main || !$product->exists){
                    $product->title = $resProduct['name'];
                    if (empty($product->name)) {
                        $product->name = $product->title;
                        foreach ($product['attributes'] as $attribute) {
                            $product->name .= $attribute['name'];
                        }
                    }
                    if (empty($product->reference)) $product->reference = $resProduct['reference'];
                    if (empty($product->ean)) $product->ean = $resProduct['barcode'];
                    if (empty($product->ean)) $product->ean = $resProduct['upc'];
                    if (empty($product->manufacturer_id)) $product->manufacturer_id = Manufacturer::firstOrCreate(['name' => $resProduct['vendor']])->id;
                    $product->save();
                    if (empty($resProduct['combination_products']) && !empty($product->combination_id)){
                        $product->deleteFromCombination();
                    }else{
                        foreach($resProduct['combination_products'] as $combRef){
                            if($combRef == $product->reference) continue;
                            try{
                                $combProduct = Product::where('reference' , $combRef)->firstOrFail();
                            }
                            catch(\Exception $e){
                                continue;
                            }
                            $combProduct->addToCombination($product->id);
                        }
                    }
                    if (!empty($resProduct['attributes'])) {
                        $attributes = [];
                        foreach ($resProduct['attributes'] as $reqAttribute) {
                            $attribute = ProductAttribute::firstOrCreate(['name' => $reqAttribute['group_name']]);
                            $attributes[$attribute->id] = ['attr_value' => $reqAttribute['name']];
                        }
                        $product->attributes()->sync($attributes);
                    }

                    if (!empty($resProduct['features'])) {
                        $characteristics=[];
                        foreach ($resProduct['features'] as $reqCharacteristic) {
                            $characteristic = ProductCharacteristic::firstOrCreate(['name' => $reqCharacteristic['name']]);
                            $characteristics[$characteristic->id] = ['attr_value' => $reqCharacteristic['value']];
                        }
                        $product->characteristics()->sync($characteristics);
                    }
                    if (!empty($resProduct['pictures'])) {
                        if($product->pictures->count()){
                            $product->pictures->map(function(ProductPicture $picture){
                                $picture->delete();
                            });
                        }
                        $arrContextOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                            ),
                        );
                        foreach ($resProduct['pictures'] as $reqPicture) {
                            try{
                                $path = str_split($product->id);
                                $path = implode('/', $path);
                                $content = file_get_contents($reqPicture['url'], false, stream_context_create($arrContextOptions));
                                $name = substr($reqPicture['url'], strrpos($reqPicture['url'],'/')+1);
                                $path = 'productPictures/' . $path.'/'.$name;
                                $stored = Storage::disk('local')->put($path, $content);
                                if(!$stored) continue;
                                $productPicture = new ProductPicture();
                                $productPicture->product_id = $product->id;
                                $productPicture->path = $path;
                                $productPicture->ordering = $product->lastPictureOrdering() + 1;
                                $productPicture->save();
                                $productPicture->url = route('product-pictures.show',['id' => $productPicture->id], false);
                                $productPicture->public_url = route('product-pictures.api.show',['id' => $productPicture->id], false);
                                $productPicture->save();
                            }catch(\Exception $e){
                                Log::channel('content_control_log')->info('Picture doesn`t exists ' . $reqPicture['url'] . ' reference:' . $resProduct['reference']);
                            }
                            
                        }
                    }
                }
                $prestaProduct = PrestaProduct::firstOrCreate(['product_id' => $product->id,'channel_id'=>$channel->id]);
                if(null == $resProduct['oldprice']){
                    $prestaProduct->price = $resProduct['price'];
                    $prestaProduct->price_discount = $resProduct['oldprice'];
                }else{
                    $prestaProduct->price = $resProduct['oldprice'];
                    $prestaProduct->price_discount = $resProduct['price'];
                }
                $prestaProduct->description = $resProduct['description'];
                $prestaProduct->is_active = $resProduct['is_active'];
                $prestaProduct->rating = $resProduct['rating'];
                $prestaProduct->save();
            }catch(\Exception $e){
                Log::error('Bottom error is for ' . $resProduct['name'] . ' from channel ' . $channel->name);
                report($e);
            }
        }
        return true;
    }
}
