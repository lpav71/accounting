<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ParserProduct
 *
 * @property int $id
 * @property string $name
 * @property string $reference
 * @property int $manufacturer_id
 * @property array $properties
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct whereManufacturerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ParserProduct query()
 */
class ParserProduct extends Model
{
    /**
     * Атрибуты, которые могут устанавливаться массово.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'reference',
        'manufacturer_id',
        'properties',
    ];

    /**
     * Атрибуты, которые должны быть приведены к собственным типам.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'reference' => 'string',
        'properties' => 'array',
    ];
}
