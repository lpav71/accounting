<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Utm
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Utm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Utm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Utm query()
 * @mixin \Eloquent
 * @property-read \App\UtmCampaign $utmCampaign
 * @property-read \App\UtmSource $utmSource
 * @property int $id
 * @property int|null $utm_campaign_id
 * @property int|null $utm_source_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $campaign
 * @property-read string|null $source
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Utm whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Utm whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Utm whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Utm whereUtmCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Utm whereUtmSourceId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 */
class Utm extends Model
{
    protected $fillable = [
        'utm_campaign_id',
        'utm_source_id',
    ];

    protected $appends = [
        'campaign',
        'source',
    ];

    /**
     * Геттер campaign
     *
     * @return string|null
     */
    public function getCampaignAttribute()
    {
        return is_null($this->utmCampaign) ? null : $this->utmCampaign->name;
    }

    /**
     * Геттер source
     *
     * @return string|null
     */
    public function getSourceAttribute()
    {
        return is_null($this->utmSource) ? null : $this->utmSource->name;
    }

    /**
     * Utm Campaign
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function utmCampaign()
    {
        return $this->belongsTo(UtmCampaign::class);
    }

    /**
     * Utm Source
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function utmSource()
    {
        return $this->belongsTo(UtmSource::class);
    }

    /**
     * Полное имя - Utm Campaign / Utm Source
     * @return string
     */
    public function getFullName()
    {
        return "{$this->utmCampaign->name}/{$this->utmSource->name}";
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
