<?php
declare(strict_types=1);

namespace App\Services\Tickets\Repositories;


use App\Ticket;
use App\TicketState;
use App\User;
use DB;

/**
 * Class TicketRepository
 * @package App\Services\Tickets\Repositories
 */
class TicketRepository
{


    /**
     * get all active tickets
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function activeTickets():\Illuminate\Database\Eloquent\Builder
    {
        $lastTicketStateId = TicketState::where('is_closed',1)->first()->id;
        $latestStates = DB::table('ticket_ticket_state')
            ->select('ticket_id', \DB::raw('MAX(created_at) as last_state_created_at'))
            ->groupBy(['ticket_id']);

        $ticketsWithoutLastStates = DB::table('ticket_ticket_state')
            ->joinSub($latestStates, 'latest_states', function ($join) {
                $join->on('ticket_ticket_state.ticket_id', '=', 'latest_states.ticket_id')
                    ->on('ticket_ticket_state.created_at', '=', 'latest_states.last_state_created_at');
            })->join('tickets', 'tickets.id', 'ticket_ticket_state.ticket_id')
            ->where('ticket_ticket_state.ticket_state_id','<>', $lastTicketStateId)
            ->pluck('ticket_ticket_state.ticket_id as id');

        return Ticket::whereIn('id',$ticketsWithoutLastStates);
    }


    /**
     * get all active tickets available for user
     *
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    public static function activeTicketsForUser(User $user): \Illuminate\Support\Collection
    {
        return self::activeTickets()->orderBy('id')->get()->filter(function (Ticket $ticket) use ($user) {
            return $ticket->userAllowed($user);
        });
    }
}