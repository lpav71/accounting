<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * App\UtmGroup
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property string $rule
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $indicator_clicks_from
 * @property int|null $indicator_clicks_to
 * @property float|null $indicator_price_per_click_from
 * @property float|null $indicator_price_per_click_to
 * @property float|null $indicator_price_per_order_from
 * @property float|null $indicator_price_per_order_to
 * @property float|null $minimum_costs
 * @property float|null $maximum_costs
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereIndicatorClicksFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereIndicatorClicksTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereIndicatorPricePerClickFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereIndicatorPricePerClickTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereIndicatorPricePerOrderFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereIndicatorPricePerOrderTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereRule($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereUpdatedAt($value)
 * @property int|null $indicator_clicks
 * @property int $sort_order
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereIndicatorClicks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmGroup whereSortOrder($value)
 */
class UtmGroup extends Model
{
    protected $fillable = [
        'name',
        'rule',
        'indicator_clicks',
        'indicator_price_per_click_from',
        'indicator_price_per_click_to',
        'indicator_price_per_order_from',
        'indicator_price_per_order_to',
        'sort_order',
        'minimum_costs',
        'maximum_costs'
    ];

    /**
     * Получение групп utm для строки
     *
     * @param string $string
     * @return Collection
     */
    public static function getGroupsForString(string $string): Collection
    {
        return UtmGroup::all()
            ->filter(
                function (UtmGroup $utmGroup) use ($string) {
                    return $utmGroup->testString(preg_replace('/^::.*::/', '', $string)) || $utmGroup->testString($string);
                }
            );
    }

    /**
     * Тестирование строки на соответствие правилу группы utm
     *
     * @param string $string
     * @return bool
     */
    protected function testString(string $string): bool
    {
        foreach (explode('||', $this->rule) as $rule) {
            if ((bool)preg_match($rule, $string)) {
                return true;
            }
        }

        return false;
    }
}
