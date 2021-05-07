<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\UtmCampaign
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmCampaign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmCampaign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmCampaign query()
 * @mixin \Eloquent
 * @property int $id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmCampaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmCampaign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmCampaign whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UtmCampaign whereUpdatedAt($value)
 * @property-read \App\ExpenseSettings $expenseSettings
 */
class UtmCampaign extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function expenseSettings()
    {
        return $this->hasOne(ExpenseSettings::class);
    }
}
