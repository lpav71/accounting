<?php

namespace App;

use App\Traits\HasValidatedParams;
use Illuminate\Database\Eloquent\Model;
use Tightenco\Parental\ReturnsChildModels;

/**
 * App\Parser
 *
 * @property int $id
 * @property bool $is_active
 * @property string $link
 * @property string|null $type
 * @property array $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser query()
 * @property string|null $name
 * @property int $interval
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PriceParserFile[] $price_parser_files
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Parser whereName($value)
 */
class Parser extends Model
{
    use ReturnsChildModels, HasValidatedParams;

    /**
     * Атрибуты, которые могут устанавливаться массово.
     *
     * @var array
     */
    protected $fillable = [
        'is_active',
        'link',
        'settings',
        'type',
        'name'
    ];

    public function price_parser_files()
    {
        return $this->hasMany('App\PriceParserFile')->orderBy('id','DESC');
    }

    /**
     * Атрибуты, которые должны быть приведены к собственным типам.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'link' => 'string',
        'settings' => 'array',
    ];

    /**
     * Параметры модели для валидации.
     *
     * @var array
     */
    public $validatedParams = [];

    /**
     * "Загрузочный" метод модели.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        static::validateOnCreating();
        static::validateOnSaving();
    }
}
