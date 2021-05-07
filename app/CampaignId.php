<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\CampaignId
 *
 * @property int $id
 * @property int $campaign_id
 * @property int $utm_campaign_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\UtmCampaign $utm_campaign
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignId newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignId newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignId query()
 * @mixin \Eloquent
 */
class CampaignId extends Model
{
    /**
     * fillable attributes
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id',
        'utm_campaign_id'
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function utm_campaign()
    {
        return $this->belongsTo(UtmCampaign::class);
    }
}
