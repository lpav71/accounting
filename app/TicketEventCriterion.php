<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketEventCriterion
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $message_substring
 * @property int|null $ticket_theme_id
 * @property int|null $creator_user_id
 * @property int|null $performer_user_id
 * @property int|null $ticket_priority_id
 * @property string|null $last_writer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User|null $creator
 * @property-read \App\User|null $performer
 * @property-read \App\TicketPriority|null $ticketPriority
 * @property-read \App\TicketTheme|null $ticketTheme
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Weekday[] $weekdays
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventCriterion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventCriterion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventCriterion query()
 * @mixin \Eloquent
 * @property int|null $messages_count
 * @property int|null $last_message_time
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TicketEventSubscription[] $ticketEventSubscription
 * @property string|null $ticket_name_substring
 */
class TicketEventCriterion extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'message_substring',
        'ticket_theme_id',
        'creator_user_id',
        'performer_user_id',
        'ticket_priority_id',
        'last_writer',
        'messages_count',
        'last_message_time',
        'ticket_name_substring'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticketTheme()
    {
        return $this->belongsTo(TicketTheme::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performer_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticketPriority()
    {
        return $this->belongsTo(TicketPriority::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function weekdays()
    {
        return $this->belongsToMany(Weekday::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticketEventSubscription(){
        return $this->belongsToMany(TicketEventSubscription::class);
    }

}
