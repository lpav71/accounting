<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Category
 *
 * @property int $id
 * @property string $name
 * @property int $is_accessory
 * @property int $is_watch
 * @property int $is_expense_accessory
 * @property int $is_certificate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category wherePrestaCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereIsWatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereIsAccessory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereIsExpenseAccessory($value)
 * @mixin \Eloquent
 * @property int|null $category_id
 * @property int $is_default
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Category[] $childCategories
 * @property-read \App\Category|null $parentCategory
 * @property-read \App\ExpenseSettings $expenseSettings
 */
class Category extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'category_id',
        'is_default',
        'is_accessory',
        'is_watch',
        'is_expense_accessory',
        'is_certificate'
    ];

    /**
     * при установке дефолтной категории сбрасывает флаг дефолтной категории у других категорий
     *
     * @param array $options
     * @return bool
     */
    public function save(array $options = []){
        if(isset($this->getDirty()['is_default'])){
            Category::where('is_default', 1)->update(['is_default' => 0]);
        }
        return parent::save($options);
    }

    /**
     * Товары, принадлежащие к данной категории
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Родительская категория
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parentCategory()
    {
        return $this->belongsTo(Category::class,'category_id');
    }

    /**
     * Дочерняя категория
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childCategories()
    {
        return $this->hasMany(Category::class,'category_id');
    }


    /**
     * Есть ли дочерняя категория с таким именем
     *
     * @param string $name
     * @return bool
     */
    public function isChild(string $name){
        foreach($this->childCategories as $child){
            if(strcasecmp($child->name, $name) == 0){
                return true;
            }
            if ($child->isChild($name)){
                return true;
            }
        }
        return false;
    }

    /**
     * добавляет в экземпляр родительские категории до корневой
     */
    public function toRootCategory(){
        if(isset($this->parentCategory)){
            $this->parentCategory;
            $this->parentCategory->toRootCategory();
        }else{
            return;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function expenseSettings()
    {
        return $this->hasOne(ExpenseSettings::class);
    }
}
