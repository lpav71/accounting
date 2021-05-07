<?php


namespace App\Services\Telegram\Commands;


use App\Services\Tickets\Repositories\TicketRepository;
use App\Ticket;
use App\User;
use Auth;
use GuzzleHttp\Client;
use Hash;
use Log;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram;

class TicketsCommand extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = 'tickets';

    /**
     * @var array Command Aliases
     */
    protected $aliases = ['ticketscommand'];

    /**
     * @var string Command Description
     */
    protected $description = "Показать доступные тикеты";

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        if (empty(Auth::user())) {
            $text = sprintf('%s' . PHP_EOL, 'Вы ещё не вошли в аккаунт');
            $this->replyWithMessage(compact('text'));
            return 'ok';
        }
        $ticketsList = sprintf('%s' . PHP_EOL, 'Доступные тикеты');
        $tickets = TicketRepository::activeTicketsForUser(Auth::user())->filter(function (Ticket $ticket){
            return !$ticket->currentState()->is_closed;
        });
        foreach ($tickets as $ticket) {
            $ticketsList .= sprintf('[%s: %s (%s)](%s)' . PHP_EOL, $ticket->id, $ticket->name, $ticket->ticketTheme->name,route('tickets.show',['id'=>$ticket->id]));
        }
        $keyboard = json_encode([
            'keyboard' => $this->ticketButtons($tickets),
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        $this->replyWithMessage([
            'text' => $ticketsList,
            'reply_markup' => $keyboard,
            'parse_mode' => 'Markdown'
        ]);

    }

    private function ticketButtons($tickets): array
    {
        $buttons = [];
        foreach ($tickets as $ticket) {
            $buttons[] = [
                'text' => '/ticket ' . $ticket->id .' ' . $ticket->name
            ];
        }
        $buttons = array_chunk($buttons, 2);
        $buttons[] = ['/tickets - все тикеты'];
        $buttons[] = ['/help - помощь'];
        array_unshift($buttons,['/ticketcreate - Создать тикет']);
        return $buttons;
    }

}