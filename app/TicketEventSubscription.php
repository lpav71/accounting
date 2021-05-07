<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketEventSubscription
 *
 * @property int $id
 * @property string $name
 * @property string $event
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TicketEventAction[] $ticketEventActions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TicketEventCriterion[] $ticketEventCriteria
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventSubscription query()
 * @mixin \Eloquent
 */
class TicketEventSubscription extends Model
{
    protected $fillable = [
        'name',
        'event'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ticketEventCriteria()
    {
        return $this->belongsToMany(TicketEventCriterion::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ticketEventActions(){
        return $this->belongsToMany(TicketEventAction::class);
    }
}
