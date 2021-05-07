<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\PriceParserFile
 *
 * @property int $id
 * @property string $name
 * @property string $path
 * @property string $url
 * @property int $is_ready
 * @property int $parser_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Parser $parser
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile whereIsReady($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile whereParserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PriceParserFile whereUrl($value)
 * @mixin \Eloquent
 */
class PriceParserFile extends Model
{
    public function parser()
    {
        return $this->belongsTo('App\Parser');
    }
}
