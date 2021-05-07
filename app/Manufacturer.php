<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Manufacturer
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Manufacturer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Manufacturer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Manufacturer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Manufacturer whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Manufacturer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Manufacturer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Manufacturer query()
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ExpenseSettings[] $expenseSettings
 */
class Manufacturer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Get manufacturer's Products
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function expenseSettings()
    {
        return $this->belongsToMany(ExpenseSettings::class, 'expense_settings_brands_expense_settings', 'brand_id', 'setting_id');
    }
}
