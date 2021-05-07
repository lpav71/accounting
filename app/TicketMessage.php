<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketMessage
 *
 * @property int $id
 * @property string $text
 * @property int $user_id
 * @property int $ticket_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Ticket $ticket
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketMessage query()
 * @mixin \Eloquent
 */
class TicketMessage extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'text',
        'user_id',
        'ticket_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
