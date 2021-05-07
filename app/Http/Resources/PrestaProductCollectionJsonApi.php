<?php

namespace App\Http\Resources;

use App\Combination;
use App\PrestaProduct as PrestaProd;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class PrestaProductCollectionJsonApi extends ResourceCollection
{

    private $channel_id = null;
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => PrestaProductJsonApi::collection($this->collection)
        ];
    }

    public function with($request)
    {

        if($this->collection->isNotEmpty()){
            $this->channel_id = $this->collection->first()->channel_id;
        }
        //получаем товары комбинаций
        $allPrestaProducts = Combination::getCombinedPrestaProducts($this->pluck('id'),$this->channel_id)
            ->with(
                'product.combination.products.prestaProducts'
                ,'product.attributes'
                ,'product.characteristics'
                ,'product.manufacturer'
                ,'product.category'
                ,'product.pictures'
            )->get()
            ->merge($this->collection)
            ->unique();
        //все товары которым нужны included
        $included = $this->getPrestaProductsIncluded($allPrestaProducts)
            ->merge(\App\Category::with('parentCategory')->get())
            ->merge($allPrestaProducts)
            ->merge($this->collection)
            ->unique();
        return [
            'included' => $this->withIncluded($included),
        ];
    }

    /**
     * получаем ресурсное представления для included
     *
     * @param $included
     * @return Collection
     */
    public function withIncluded(Collection $included):Collection
    {
        return $included->map(
            function ($include) {
                if ($include instanceof \App\ProductPicture) {
                    return new ProductPictureJsonApi($include);
                }
                if ($include instanceof \App\Combination) {
                    return new CombinationJsonApi($include,$this->channel_id);
                }
                if($include instanceof \App\PrestaProduct){
                    return new PrestaProductJsonApi($include);
                }
                if($include instanceof \App\Category){
                    return new CategoryJsonApi($include);
                }
                if($include instanceof \App\ProductCharacteristic){
                    return new CharacteristicJsonApi($include);
                }
                if($include instanceof \App\ProductAttribute){
                    return new AttributeJsonApi($include);
                }
                if($include instanceof \App\Manufacturer){
                    return new ManufacturerJsonApi($include);
                }
            }
        );
    }


    /**
     *
     * получаем included для товаров
     * @param $prestaProducts
     * @return mixed
     */
    public function getPrestaProductsIncluded(Collection $prestaProducts)
    {
        $productPictures = $prestaProducts->flatMap(function (PrestaProd $prestaProduct) {
                return $prestaProduct->product->pictures;
            });
        $combinations = $prestaProducts
            ->map(function (PrestaProd $prestaProduct) {
                return $prestaProduct->product->combination;
            })->whereInstanceOf(\App\Combination::class);
        $characteristics = $prestaProducts
            ->flatMap(function (PrestaProd $prestaProduct){
                return $prestaProduct->product->characteristics;
            });
        $attributes = $prestaProducts
            ->flatMap(function (PrestaProd $prestaProduct){
                return $prestaProduct->product->attributes;
            })->whereInstanceOf(\App\ProductAttribute::class);
        $manufacturers = $prestaProducts
            ->map(function (PrestaProd $prestaProduct){
                return $prestaProduct->product->manufacturer;
            })->whereInstanceOf(\App\Manufacturer::class);
        return $productPictures->merge($combinations)->merge($characteristics)->merge($attributes)->merge($manufacturers)->unique();

    }
}
