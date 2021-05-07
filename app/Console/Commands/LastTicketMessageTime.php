<?php

namespace App\Console\Commands;

use App\Services\Tickets\Events\TicketLastMessageTime;
use App\Services\Tickets\Repositories\TicketRepository;
use App\Ticket;
use Illuminate\Console\Command;

class LastTicketMessageTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket:last-message-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tickets = TicketRepository::activeTickets()->get();
        foreach($tickets as $ticket){
            (new TicketLastMessageTime($ticket))->execute();
        }
        return true;
    }
}
