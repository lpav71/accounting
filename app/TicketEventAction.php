<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketEventAction
 *
 * @property int $id
 * @property string $name
 * @property int|null $add_user_id
 * @property string|null $auto_message
 * @property int|null $ticket_priority_id
 * @property int|null $performer_user_id
 * @property string|null $message_replace
 * @property string|null $notify
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User|null $addUser
 * @property-read \App\User|null $performer
 * @property-read \App\TicketPriority|null $ticketPriority
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TicketEventAction query()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TicketEventSubscription[] $ticketEventSubscription
 * @property-read \App\User|null $user
 */
class TicketEventAction extends Model
{
    protected $fillable = [
        'name',
        'add_user_id',
        'auto_message',
        'ticket_priority_id',
        'performer_user_id',
        'message_replace',
        'notify'
    ];

    /**
     * добавить в чат пользователя такого-то
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function addUser(){
        return $this->belongsTo(User::class,'add_user_id');
    }

    /**
     * изменить ответственного по тикету
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function performer(){
        return $this->belongsTo(User::class,'performer_user_id');
    }

    /**
     * изменить срочность тикета
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticketPriority(){
        return $this->belongsTo(TicketPriority::class);
    }

    /**
     * добавить в чат пользователей из списка при условии что он сейчас работает
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(){
        return $this->belongsToMany(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(){
        return $this->belongsTo(User::class,'add_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticketEventSubscription(){
        return $this->belongsToMany(TicketEventSubscription::class,'');
    }
}
