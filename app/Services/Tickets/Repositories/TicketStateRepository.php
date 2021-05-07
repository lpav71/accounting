<?php
declare(strict_types=1);

namespace App\Services\Tickets\Repositories;


use App\Services\Tickets\Exceptions\DefaultTicketStateNotSetException;
use App\TicketState;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class TicketStateRepository
 * @package App\Services\Tickets\Repositories
 */
class TicketStateRepository
{
    /**
     * get default Ticket State
     *
     * @return TicketState
     * @throws DefaultTicketStateNotSetException
     */
    public static function getDefault(): TicketState
    {
        $ticketState = TicketState::where('is_default', 1)->first();
        if (empty($ticketState)) {
            throw new DefaultTicketStateNotSetException();
        }
        return $ticketState;
    }

    /**
     * @return Collection
     */
    public static function all(): Collection
    {
        return TicketState::all();
    }
}