<?php
declare(strict_types=1);

namespace App\Services\Tickets\Factories;


use App\Services\Tickets\Events\TicketCreatedEvent;
use App\Services\Tickets\Events\TicketMessageAddedEvent;
use App\Ticket;
use App\TicketMessage;
use App\User;
use Illuminate\Database\Eloquent\Collection;

class TicketMessageFactory
{

    public static function build(string $text, User $user, Ticket $ticket):TicketMessage
    {
        $message = new TicketMessage([
                'text' => $text,
                'user_id' => $user->id,
                'ticket_id' => $ticket->id
            ]
        );
        if ($ticket->ticketMessages->count() == 0){
            $string = $text;
            if(mb_strlen($text) > 240){
                $string = substr($string,0,240).'...';
            }
            $ticket->name = $string;
            $ticket->save();
        }
        $message->save();
        (new TicketMessageAddedEvent($ticket, $message,$user))->execute();
        return $message;
    }

    public static function buildByIds(string $text, int $user_id, int $ticket_id):TicketMessage
    {
        return self::build(
            $text,
            User::find($user_id),
            Ticket::find($ticket_id)
        );
    }

    public static function getByTicket(Ticket $ticket): Collection
    {
        return $ticket->ticketMessages;
    }

    public static function getByTicketId(int $ticket_id): Collection
    {
        $ticket = Ticket::find($ticket_id);
        return self::getByTicket($ticket);
    }

}