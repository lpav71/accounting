<?php
declare(strict_types=1);

namespace App\Services\Tickets\Factories;


use App\Order;
use App\Services\Tickets\Events\TicketCreatedEvent;
use App\Services\Tickets\Exceptions\DefaultTicketStateNotSetException;
use App\Services\Tickets\Repositories\TicketStateRepository;
use App\Ticket;
use App\TicketPriority;
use App\TicketState;
use App\TicketTheme;
use App\User;
use DB;

/**
 * Class TicketFactory
 * @package App\Services\Tickets\Factories
 */
class TicketFactory
{

    /**
     * @param string $name
     * @param int $order_id
     * @param int $creator_id
     * @param int $ticketPriority_id
     * @param int $ticketCategory_id
     * @param int|null $ticketState_id
     * @param int|null $performer_id
     * @return Ticket
     * @throws DefaultTicketStateNotSetException
     */
    public static function buildByIds(int $creator_id, int $ticketPriority_id, int $ticketCategory_id, int $ticketState_id = null, int $performer_id = null, int $order_id =null): Ticket
    {
        $ticketState = null;
        if (!is_null($ticketState_id)) {
            $ticketState = TicketState::find($ticketState_id);
        }

        $performer = null;
        if (!is_null($performer_id)) {
            $performer = User::find($performer_id);
        }

        $order = null;
        if (!is_null($order_id)) {
            $order = Order::find($order_id);
        }

        return self::build(
            User::find($creator_id),
            TicketPriority::find($ticketPriority_id),
            TicketTheme::find($ticketCategory_id),
            $ticketState,
            $performer,
            $order
        );
    }

    /**
     * @param string $name
     * @param Order $order
     * @param User $creator
     * @param TicketPriority $ticketPriority
     * @param TicketTheme $ticketTheme
     * @param TicketState|null $ticketState
     * @param User|null $performer
     * @return Ticket
     * @throws DefaultTicketStateNotSetException
     */
    public static function build(User $creator, TicketPriority $ticketPriority, TicketTheme $ticketTheme, TicketState $ticketState = null, User $performer = null, Order $order = null): Ticket
    {
        if (is_null($ticketState)) {
            try {
                $ticketState = TicketStateRepository::getDefault();
            } catch (DefaultTicketStateNotSetException $e) {
                throw $e;
            }
        }
        $statement = DB::select("SHOW TABLE STATUS LIKE 'tickets'");
        $nextId = $statement[0]->Auto_increment;
        $ticket =  new Ticket(
            [
                'name' => 'Ticket '.$nextId,
                'order_id' => !is_null($order) ? $order->id : null,
                'creator_user_id' => $creator->id,
                'performer_user_id' => !is_null($performer) ? $performer->id : null,
                'ticket_priority_id' => $ticketPriority->id,
                'ticket_theme_id' => $ticketTheme->id,
            ]
        );
        $ticket->save();
        $ticket->states()->save($ticketState);
        $ticket->addUser($creator);
        if(!is_null($performer)){
            $ticket->setPerformer($performer);
            $ticket->addUser($performer);
        }
        (new TicketCreatedEvent($ticket))->execute();
        return $ticket;
    }


    public static function update(Ticket $ticket, string $name = null, TicketPriority $ticketPriority = null, TicketTheme $ticketTheme = null, TicketState $ticketState = null, User $performer = null, Order $order = null):Ticket{

        if(!is_null($name)){
            $ticket->name = $name;
        }

        if(!is_null($ticketPriority)){
            $ticket->ticket_priority_id = $ticketPriority->id;
        }

        if(!is_null($ticketTheme)){
            $ticket->ticket_theme_id = $ticketTheme->id;
        }

        if(!is_null($ticketState)){
            $ticket->states()->save($ticketState);
        }

        if(!is_null($performer)){
            $ticket->performer_user_id = $performer->id;
        }

        if(!is_null($order)){
            $ticket->order_id = $order->id;
        }

        $ticket->save();

        return $ticket;
    }

}