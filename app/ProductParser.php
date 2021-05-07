<?php

namespace App;

use Tightenco\Parental\HasParentModel;

/**
 * App\ProductParser
 *
 * @property int $id
 * @property bool $is_active
 * @property string $link
 * @property string|null $type
 * @property array $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ParserProduct[] $parserProducts
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser query()
 * @property string|null $name
 * @property int $interval
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductParser whereName($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PriceParserFile[] $price_parser_files
 */
class ProductParser extends Parser
{
    use HasParentModel;

    /**
     * Параметры модели для валидации.
     *
     * @var array
     */
    public $validatedParams = [
        'settings' => [
            'productSelectorInList' => 'required|string',
            'pageSelector' => 'required|string',
            'productManufacturerSelector' => 'required|string',
            'productModelSelector' => 'required|string',
            'productReferenceSelector' => 'required|string',
            'productPriceSelector' => 'required|string',
            'productOldPriceSelector' => 'required|string',
            'productImageSelector' => 'required|string',
            'productAttributeSelector' => 'required|string',
            'productAttributeSelector-Name' => 'required|string',
            'productAttributeSelector-Value' => 'required|string',
            'channelCategory' => 'required|string'
        ],
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parserProducts()
    {
        return $this->belongsToMany('App\ParserProduct')->withPivot('created_at');
    }
}
