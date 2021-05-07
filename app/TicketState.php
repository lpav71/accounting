<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketState
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TicketState[] $nextStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TicketState[] $previousStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketState query()
 * @mixin \Eloquent
 * @property int $is_default
 * @property int $is_closed
 */
class TicketState extends Model
{
    /**
     * fillable attributes
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'is_default',
        'is_closed'
    ];


    /**
     * Get Order state's previous states
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStates()
    {
        return $this
            ->belongsToMany(TicketState::class,
                'ticket_state_ticket_state',
                'next_ticket_state_id',
                'ticket_state_id');
    }

    /**
     * Get Order state's next states
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nextStates()
    {
        return $this
            ->belongsToMany(
                TicketState::class,
                'ticket_state_ticket_state',
                'ticket_state_id',
                'next_ticket_state_id');
    }
}
