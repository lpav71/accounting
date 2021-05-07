<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Combination
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Combination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Combination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Combination query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Combination whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Combination whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Combination whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $onlyProducts
 */
class Combination extends Model
{
    /**
     * Products with needed relations
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany('App\Product');
    }

    /**
     * only related Products
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function onlyProducts()
    {
        return $this->hasMany('App\Product');
    }

    /**
     * get PrestaProducts that exists for combination
     *
     * @param $channel_id
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function prestaProducts($channel_id)
    {
        $return = $this->onlyProducts->map(function (Product $product) use ($channel_id) {
            return $product->prestaProducts->where('channel_id', $channel_id)->first();
        })->whereInstanceOf(PrestaProduct::class);
        return $return;
    }

    /**
     * get products that exists on channel
     *
     * @param $channel_id
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function productsChannel($channel_id)
    {
        return $this->products->filter(function (Product $product) use ($channel_id) {
            return !is_null($product->prestaProducts->where('channel_id', $channel_id)->first());
        });
    }

    /**
     * is there more than 1 product for channel exists for channel
     *
     * @param $channel_id
     * @return bool
     */
    public function hasChannelCombination($channel_id): bool
    {
        return $this->products->filter(function (Product $product) use ($channel_id) {
                return !is_null($product->prestaProducts->where('channel_id', $channel_id)->first());
            })->count() > 1;
    }

    /**
     * @param $ids
     * @param $channelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getCombinedPrestaProducts($ids, $channelId)
    {
        $prestaProductIds = \DB::table('presta_products')
            ->join('products', 'presta_products.product_id', '=', 'products.id')
            ->whereIn('products.combination_id', function ($query) use ($ids) {
                $query->select('products.combination_id')
                    ->from('presta_products')
                    ->join('products', 'presta_products.product_id', 'products.id')
                    ->join('combinations', 'products.combination_id', 'combinations.id')
                    ->whereIn('presta_products.id', $ids);
            })
            ->where('presta_products.channel_id', $channelId)
            ->select('presta_products.id')
            ->get()
            ->pluck('id')
            ->toArray();
        return PrestaProduct::whereIn('id',$prestaProductIds);
    }
}
