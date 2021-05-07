<?php

namespace App\Http\Resources;

use App\Combination;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use App\PrestaProduct as PrestaProd;

class PrestaProductPriceCollectionJsonApi extends ResourceCollection
{
    private $channel_id = null;
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => PrestaProductPriceJsonApi::collection($this->collection)
        ];
    }

    public function with($request)
    {

        if($this->collection->isNotEmpty()){
            $this->channel_id = $this->collection->first()->channel_id;
        }
        //получаем товары комбинаций
        $ids = $this->pluck('id');
        $allPrestaProducts = Combination::getCombinedPrestaProducts($ids,$this->channel_id)->with('product','product.combination.products.prestaProducts')->get();
        //все товары которым нужны included
        $included = $this->getPrestaProductsIncluded($allPrestaProducts)->merge($allPrestaProducts);
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
                if ($include instanceof \App\Combination) {
                    return new CombinationJsonApi($include,$this->channel_id);
                }
                if($include instanceof \App\PrestaProduct){
                    return new PrestaProductPriceJsonApi($include);
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
        $combinations = $this->collection
            ->map(function (PrestaProd $prestaProduct) {
                return $prestaProduct->product->combination;
            })->whereInstanceOf(\App\Combination::class);
        return $combinations->unique();

    }
}
