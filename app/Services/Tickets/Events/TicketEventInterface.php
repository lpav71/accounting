<?php
declare(strict_types=1);

namespace App\Services\Tickets\Events;


use App\Ticket;
use App\TicketMessage;

/**
 * Interface TicketEventInterface
 * @package App\Services\Tickets\Events
 */
interface TicketEventInterface
{

    /**
     * TicketEventInterface constructor.
     * @param Ticket $ticket
     * @param TicketMessage|null $ticketMessage
     */
    public function __construct(Ticket $ticket, TicketMessage $ticketMessage = null);

    /**
     * @return void
     */
    function execute(): void;

}