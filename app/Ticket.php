<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\LaravelFilterable\Filterable;
use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Kyslik\ColumnSortable\Sortable;
use App\Traits\ModelLogger;

/**
 * App\Ticket
 *
 * @property int $id
 * @property string $name
 * @property int $order_id
 * @property int $creator
 * @property int $performer
 * @property int $ticket_priority_id
 * @property int $ticket_theme_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Ticket query()
 * @mixin \Eloquent
 * @property int $creator_user_id
 * @property int|null $performer_user_id
 * @property-read \App\Order|null $order
 * @property-read \App\TicketPriority $priority
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TicketState[] $states
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TicketMessage[] $ticketMessages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @property-read \App\TicketTheme $ticketTheme
 */
class Ticket extends Model
{
    use Filterable, Sortable;
    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'order_id',
        'creator_user_id',
        'performer_user_id',
        'ticket_priority_id',
        'ticket_theme_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function states()
    {
        return $this
            ->belongsToMany(TicketState::class)
            ->withPivot('created_at')
            ->orderBy('ticket_ticket_state.id', 'desc');
    }

    /**
     * current state
     *
     * @return mixed|null
     */
    public function currentState(){
        return $this->states()->first();
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priority()
    {
        return $this->belongsTo(TicketPriority::class, 'ticket_priority_id');
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
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticketMessages()
    {
        return $this->hasMany(TicketMessage::class)->with('user');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function getLastMessage()
    {
        return $this->ticketMessages()->orderBy('id', 'desc')->first();
    }

    /**
     * @param User $user
     * @return bool|string
     */
    public function getChatRole(User $user)
    {
        if ($this->performer_user_id == $user->id) {
            return 'PERFORMER';
        }
        if ($this->creator_user_id == $user->id) {
            return 'CREATOR';
        }
        if (in_array($user->id, $this->users->pluck('id')->toArray())) {
            return 'OTHER';
        }
        return false;
    }

    /**
     * add user
     *
     * @param User $user
     * @return bool
     */
    public function addUser(User $user): bool
    {
        if($this->users->contains($user)){
            return true;
        }
        $this->users()->attach($user);
        return true;
    }

    /**
     * set performer
     *
     * @param User $user
     * @return bool
     */
    public function setPerformer(User $user): bool
    {
        $this->performer_user_id = $user->id;
        return $this->save();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function userAllowed(User $user):bool {
        if($user->hasRole('admin')){
            return true;
        }
        if($this->users->contains($user) || $this->creator_user_id == $user->id || $this->performer_user_id == $user->id){
            return true;
        }
        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticketTheme(){
        return $this->belongsTo(TicketTheme::class);
    }
}
