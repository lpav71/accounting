<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProductCharacteristic
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductCharacteristic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductCharacteristic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductCharacteristic query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductCharacteristic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductCharacteristic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductCharacteristic whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductCharacteristic whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProductCharacteristic extends Model
{
    protected $fillable = ['name'];

    /**
     * Продукты у которых присутствует данный атрибут
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     */
    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('attr_value');
    }
}
