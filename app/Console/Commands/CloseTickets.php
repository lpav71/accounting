<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Tickets\Factories\TicketFactory;
use App\Ticket;
use App\TicketState;
use Illuminate\Support\Carbon;
class CloseTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:autoclose-tickets';

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
        $tickets = Ticket::all() ;
        foreach($tickets as $k => $ticket){
        $newState = TicketState::where('is_closed', 1)->first();
        TicketFactory::update($ticket, null, null, null, $newState);
        }
    }
}
