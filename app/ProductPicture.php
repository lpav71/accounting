<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Imagick;

/**
 * Изображение товара
 * 
 * App\ProductPicture
 *
 * @property int $id
 * @property int $product_id
 * @property string $url
 * @property string $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Product $manufacturer
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductPicture whereUrl($value)
 * @mixin \Eloquent
 * @property int $ordering
 * @property-read \App\Product $product
 * @property string $public_url
 * @property string $hash
 */
class ProductPicture extends Model
{

    protected $fillable = [
        'product_id',
        'hash',
        'url',
        'path',
        'ordering',
        'public_url'
    ];
    /**
     * Товар к которой относится изображение
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Удаляет файл изображения перед удалением модели
     *
     * @return bool
     * @throws \Exception
     */
    public function delete(){
        
        if(! Storage::disk('local')->delete($this->path)){
            return false;
        }
        
        return parent::delete();
    }

    public function save(array $options = []){
        if(!$this->exists){
            self::processImage($this->path);
        }
        return parent::save($options);
    }

    /**
     * обработка изображения товаров
     *
     * @param $path
     * @throws \ImagickException
     */
    public static function processImage($path){
        $im = new Imagick(storage_path('app/') . $path);
        $im->setImageBackgroundColor('white');
        $im->setImageAlphaChannel(11); // Imagick::ALPHACHANNEL_REMOVE
        $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $im->trimImage(1000);
        $im->thumbnailImage(0, 1000, false);
        $im->writeImage(storage_path('app/') . $path);
    }

    /**
     * @param ProductPicture $productPicture
     * @return string
     */
    public function getHash()
    {
        if (!empty($productPicture->hash)) {
            return $productPicture->hash;
        }
        $hash = hash_file('CRC32', Storage::disk('local')->path($this->path));
        $this->update(['hash' => $hash]);
        return $this->hash;
    }
}
