<?php

namespace App;

use App\Combination as CombinationModel;
use App\Services\Channel\UploadProductService;
use App\Traits\ProtectedOperable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kyslik\ColumnSortable\Sortable;
use Kyslik\LaravelFilterable\Filterable;
use Unirest;

/**
 * App\Product
 *
 * @property int $id
 * @property string $name
 * @property string $reference
 * @property string|null $ean
 * @property int $manufacturer_id
 * @property int $is_composite
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $need_guarantee
 * @property float $wholesale_price
 * @property-read \App\Manufacturer $manufacturer
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetail[] $orderDetails
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product filter(\Kyslik\LaravelFilterable\FilterContract $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product sortable($defaultParameters = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereEan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereIsComposite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereManufacturerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereNeedGuarantee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereWholesalePrice($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product query()
 * @property int $category_id
 * @property-read \App\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductPicture[] $pictures
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereCategoryId($value)
 * @property string|null $combination_id
 * @property string $title
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductAttribute[] $attributes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductCharacteristic[] $characteristics
 * @property-read \App\Combination|null $combination
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereCombinationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereTitle($value)
 * @property-read \App\PrestaProduct $activePrestaProducts
 * @property-read \App\PrestaProduct $presta_products
 * @property-read \App\PrestaProduct $prestaProducts
 */
class Product extends Model
{
    use ProtectedOperable, Filterable, Sortable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 
        'reference', 
        'ean', 
        'manufacturer_id', 
        'is_composite', 
        'need_guarantee', 
        'wholesale_price',
        'category_id',
        'combination_id',
        'title'
    ];

    /**
     * Sortable fields
     *
     * @var array
     */
    public $sortable = ['id', 'name', 'reference', 'ean', 'manufacturer_id', 'is_composite'];

    /**
     * Get Product's Manufacturer
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Get CompositeProduct's products
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_product', 'composite_product_id', 'product_id')->withTimestamps();
    }

    /**
     * Get parent CompositeProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parent()
    {
        return $this->belongsToMany(Product::class, 'product_product', 'product_id', 'composite_product_id')->withTimestamps();
    }

    /**
     * Get product's Order Details
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    /**
     * Is composite product?
     *
     * @return bool
     */
    public function isComposite()
    {
        return $this->is_composite;
    }

    /**
     * Предоставляется ли гарантия?
     *
     * @return bool
     */
    public function isNeedGuarantee(): bool
    {
        return (bool) $this->need_guarantee;
    }

    /**
     * Assign the given product to the model.
     *
     * @param array|string|\App\Product ...$products
     * @return Product
     */
    public function assignProduct(...$products)
    {
        if ($this->isComposite()) {
            $products = collect($products)->flatten()->map(function ($product) {
                return $this->getStoredProduct($product);
            })->all();

            $this->products()->saveMany($products);
        }

        return $this;
    }

    /**
     * get Product by id, name or Product
     *
     * @param integer|string|Product $product
     * @return Product
     */
    protected function getStoredProduct($product): Product
    {
        if (is_numeric($product)) {
            return Product::query()->where('id', $product)->first();
        }

        if (is_string($product)) {
            return Product::query()->where('name', $product)->first();
        }

        return $product;
    }

    /**
     * Get CurrentQuantity by taking into account possible combinations of subordinate products
     *
     * @param \App\Store $store
     * @return int
     */
    public function getCombinedQuantity(Store $store)
    {
        if ($this->isComposite()) {
            return $this->products->map(function (Product $product) use ($store) {
                return $product->getCombinedQuantity($store);
            })->min();
        } else {
            return $store->getCurrentQuantity($this->id);
        }
    }

    /**
     * Get RealCurrentQuantity by taking into account possible combinations of subordinate products
     *
     * @param \App\Store $store
     * @return int
     */
    public function getRealCombinedQuantity(Store $store)
    {
        if ($this->isComposite()) {
            return $this->products->map(function (Product $product) use ($store) {
                return $product->getRealCombinedQuantity($store);
            })->min();
        } else {
            return $store->getRealCurrentQuantity($this->id);
        }
    }

    /**
     * Get ReservedQuantity by taking into account possible combinations of subordinate products
     *
     * @param \App\Store $store
     * @return int
     */
    public function getReservedCombinedQuantity(Store $store)
    {
        if ($this->isComposite()) {
            return $this->products->map(function (Product $product) use ($store) {
                return $product->getReservedCombinedQuantity($store);
            })->min();
        } else {
            return $store->getReservedQuantity($this->id);
        }
    }

    /**
     * Свободный остаток по складу после операции
     *
     * @param Store $store
     * @param Operation $operation
     * @return int|float
     */
    public function getCombinedQuantityAfterOperation(Store $store, Operation $operation)
    {
        if ($this->isComposite()) {
            return $this->products->map(function (Product $product) use ($store, $operation) {
                return $product->getCombinedQuantityAfterOperation($store, $operation);
            })->min();
        } else {
            return $store->getCurrentQuantityAfterOperation($this->id, $operation);
        }
    }

    /**
     * Реальный остаток по складу после операции
     *
     * @param Store $store
     * @param Operation $operation
     * @return int|float
     */
    public function getRealCombinedQuantityAfterOperation(Store $store, Operation $operation)
    {
        if ($this->isComposite()) {
            return $this->products->map(function (Product $product) use ($store, $operation) {
                return $product->getRealCombinedQuantityAfterOperation($store, $operation);
            })->min();
        } else {
            return $store->getRealCurrentQuantityAfterOperation($this->id, $operation);
        }
    }

    /**
     * Резервный остаток по складу после операции
     *
     * @param Store $store
     * @param Operation $operation
     * @return int|float
     */
    public function getReservedCombinedQuantityAfterOperation(Store $store, Operation $operation)
    {
        if ($this->isComposite()) {
            return $this->products->map(function (Product $product) use ($store, $operation) {
                return $product->getReservedCombinedQuantityAfterOperation($store, $operation);
            })->min();
        } else {
            return $store->getReservedQuantityAfterOperation($this->id, $operation);
        }
    }

    /**
     * Get Simple Products
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSimpleProducts()
    {
        $result = collect([]);
        self::addSimpleProductsToCollection($this, $result);

        return $result;
    }

    /**
     * @param \App\Product $product
     * @param \Illuminate\Support\Collection $result
     */
    protected static function addSimpleProductsToCollection(Product $product, Collection &$result)
    {
        if ($product->isComposite()) {
            $product->products->each(function (Product $childProduct) use ($result) {
                Product::addSimpleProductsToCollection($childProduct, $result);
            });
        } else {
            $result->push($product);
        }
    }

    /**
     * Is used in Operations
     *
     * @return bool
     */
    public function isUsedInOperations()
    {
        return (bool) $this->operations->count();
    }

    /**
     * Is used in Orders
     *
     * @return bool
     */
    public function isUsedInOrders()
    {
        return (bool) $this->orderDetails->count();
    }


   /**
     * Products for channel
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prestaProducts()
    {
        return $this->hasMany(PrestaProduct::class);
    }

    /**
     * Product category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Product pictures
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pictures()
    {
        return $this->hasMany(ProductPicture::class)->orderBy('ordering');
    }

    /**
     * Product Combinations
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function combination()
    {
        return $this->belongsTo(Combination::class);
    }

    /**
     * Add Product to another Product`s combination
     * Or Create new combination with both of them
     *
     * @param $productId
     * @return bool
     * @throws \Exception
     */
    public function addToCombination($productId)
    {
        $productCombination = Product::find($productId);
        if(isset($productCombination->combination)){
            if(isset($this->combination)){
                if($this->combination->id == $productCombination->combination->id){
                    return true;
                }else{
                    $this->deleteFromCombination();
                }
            }
            $this->combination_id=$productCombination->combination->id;
        }else{
            $combination = CombinationModel::create();
            $productCombination->combination_id = $combination->id;
            $productCombination->save();
            $this->combination_id = $combination->id;
        }
        $this->save();
        return true;
    }

    /**
     * Delete product from combination and delete combination if it will be with one product
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteFromCombination(){
        if(isset($this->combination) && $this->combination->products->count()==2){
            $combination=$this->combination;
            $combination->products->map(function (Product $product) {
                $product->combination_id = null;
                $product->save();
            });
            $combination->delete();
        }else{
        $this->combination_id=null;
        }
        $this->save();
        return true;
    }

    /**
     * Product atributes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function attributes()
    {
        return $this->belongsToMany(ProductAttribute::class)->withPivot(['attr_value', 'id']);
    }

    /**
     * all Product attributes with values if exists
     *
     * @return array
     */
    public function allAttributes()
    {
        $id = $this->id;
        if (empty($id)) {
            $id = 0;
        }
        $b = DB::raw('SELECT * FROM product_attributes LEFT JOIN ( SELECT product_product_attribute.`product_id`, product_product_attribute.`attr_value`, product_product_attribute.`product_attribute_id` FROM product_product_attribute LEFT JOIN products ON product_product_attribute.`product_id` = products.id WHERE `product_id` = ' . $id . ') as a ON a.`product_attribute_id` = product_attributes.id ORDER BY id');
        return DB::select($b);
        //TODO переделать в норм запрос
    }

    /**
     * Product characteristics
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function characteristics()
    {
        return $this->belongsToMany(ProductCharacteristic::class)->withPivot('attr_value')->withPivot('id');
    }

    /**
     * all Product characteristics with values if exists
     *
     * @return array
     */
    public function allCharacteristics()
    {
        $id = $this->id;
        if (empty($id)) {
            $id = 0;
        }
        $b = DB::raw('SELECT * FROM product_characteristics LEFT JOIN ( SELECT product_product_characteristic.`product_id`, product_product_characteristic.`attr_value`, product_product_characteristic.`product_characteristic_id` FROM product_product_characteristic LEFT JOIN products ON product_product_characteristic.`product_id` = products.id WHERE `product_id` = ' . $id . ') as a ON a.`product_characteristic_id` = product_characteristics.id ORDER BY id');
        return DB::select($b);
        //TODO переделать в норм запрос
    }


    /**
     * Удаляет Pictures и PrestaProducts стирает асоциируемые атрибуты и характеристики которые относятся к этому товару
     *
     * @return bool|void|null
     * @throws \Exception
     */
    public function delete(){

        if($this->isUsedInOperations()){
            throw new \Exception('This Product id='. $this->id .' can not be deleted, Used in operations');
        }

        if($this->isUsedInOrders()){
            throw new \Exception('This Product id='. $this->id .' can not be deleted, part of orders');
        }

        if($this->parent()->count()){
            throw new \Exception('This Product id='. $this->id .' can not be deleted, part of composite products');
        }
        
        $this->pictures->map(function(ProductPicture $picture){
            $picture->delete();
        });
        $this->prestaProducts->map(function(PrestaProduct $prestaProduct){
            $prestaProduct->delete();
        });

        $this->attributes()->detach();

        $this->characteristics()->detach();

        $this->deleteFromCombination();

        return parent::delete();
    }

    /**
     * get last picture ordering index
     *
     * @return int|mixed
     */
    public function lastPictureOrdering()
    {
        if($this->pictures()->count() == 0){
            return 0;
        }
        return $this->hasMany(ProductPicture::class)->orderBy('ordering','desc')->first()->ordering;
    }

    /**
     * get main(first) product picture
     *
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function mainPicture(){
        if($this->pictures()->count() == 0){
            return null;
        }
        return $this->pictures()->first();
    }

    /**
     * upload product to channel
     *
     * @return bool
     */
    public function uploadToChannels(){
        $this->prestaProducts->map(function(PrestaProduct $prestaProduct){
            $collection = collect();
            $collection->push($prestaProduct);
            UploadProductService::upload($collection);
        });
        return true;
    }

    /**
     * make copy of product
     *
     * @return Model
     */
    public function makeCopy(){

        $model = $this;
        $model->load('characteristics','attributes');

        $newModel = $model->replicate();
        $newModel->push();

        $newModel->reference = $newModel->reference.' copy'.$newModel->id;
        $newModel->name = $newModel->name.' copy'.$newModel->id;
        // Once the model has been saved with a new ID, we can get its children
        foreach ($newModel->getRelations() as $relation => $items) {
            foreach ($items as $item) {
                // Now we get the extra attributes from the pivot tables, but
                // we intentionally leave out the foreignKey, as we already
                // have it in the newModel
                $extra_attributes = array_except($item->pivot->getAttributes(), $item->pivot->getForeignKey());
                $extra_attributes = array_except($extra_attributes,$item->pivot->getKeyName());
                $newModel->{$relation}()->attach($item, $extra_attributes);
            }
        }
        $newModel->save();
        return $newModel;
    }

    /**
     * Получение себестоимости по курсу
     *
     * @param Carbon|null $date
     * @return float|int
     */
    public function getPrice(?Carbon $date = null)
    {
        try {
            if ($date) {
                $date = $date->format('d/m/Y');
                if (\Cache::has('usd_rate-' . $date)) {
                    $usdRate = \Cache::get('usd_rate-' . $date);
                } else {
                    $response = Unirest\Request::get('http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $date);
                    if ($response->code == 200) {
                        $response = new \SimpleXMLElement($response->body);
                        foreach ($response->Valute as $item) {
                            if ($item->CharCode == 'USD') {
                                $usdRate = (float)$item->Value;
                                \Cache::put('usd_rate-' . $date, $usdRate, 1440);
                                break;
                            }
                        }
                    }
                }
            } else {
                if (\Cache::has('usd_rate')) {
                    $usdRate = \Cache::get('usd_rate');
                } else {
                    $response = Unirest\Request::get('https://www.cbr-xml-daily.ru/daily_json.js');
                    if ($response->code == '200' && $response->body instanceof \stdClass) {
                        $usdRate = (float)$response->body->Valute->USD->Value;
                        \Cache::put('usd_rate', $usdRate, 1440);
                    }
                }
            }

            return $usdRate * $this->wholesale_price;
        } catch (\Exception $exception) {
            \Session::flash('warning', __('Problems with getting the usd rate, the price in usd'));
            return $this->wholesale_price;
        }
    }

    /**
     * Get all products in composite product
     *
     * @param Product $product
     * @return Collection
     */
    public static function getAllProducts(Product $product)
    {
        $allProducts = collect();
        if($product->is_composite) {
            foreach ($product->products as $partProduct) {
                if($partProduct->is_composite) {
                    $allProducts->push(self::getAllProducts($partProduct));
                    $allProducts = $allProducts->collapse();
                } else {
                    $allProducts->push($partProduct);
                }
            }
        } else {
            $allProducts->push($product);
        }

        return $allProducts;
    }
}
