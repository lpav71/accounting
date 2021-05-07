<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Атрибут товара для комбинаций
 * App\ProductAttribute
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProductAttribute extends Model
{
    /**
     * fillable attributes
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Продукты у которых есть данный атрибут со значением в pivot attr_value
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('attr_value');
    }
}
