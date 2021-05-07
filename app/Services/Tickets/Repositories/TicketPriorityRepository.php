<?php
declare(strict_types=1);


namespace App\Services\Tickets\Repositories;


use App\Services\Tickets\Exceptions\DefaultTicketPriorityNotSetException;
use App\TicketPriority;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class TicketPriorityRepository
 * @package App\Services\Tickets\Repositories
 */
class TicketPriorityRepository
{

    /**
     * @return TicketPriority
     * @throws DefaultTicketPriorityNotSetException
     */
    public static function getDefault(): TicketPriority
    {
        $ticketState = TicketPriority::where('is_default', 1)->first();
        if (empty($ticketState)) {
            throw new DefaultTicketPriorityNotSetException();
        }
        return $ticketState;
    }


    /**
     * @return Collection
     */
    public static function all(): Collection
    {
        return TicketPriority::all();
    }

}