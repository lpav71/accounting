<?php


namespace App\Services\Telegram\Commands;


use App\Services\Tickets\Factories\TicketFactory;
use App\Services\Tickets\Factories\TicketMessageFactory;
use App\Services\Tickets\Repositories\TicketRepository;
use App\Ticket;
use App\TicketState;
use App\User;
use Auth;
use Log;
use Telegram;
use Telegram\Bot\Commands\Command;

class TicketCloseCommand extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = 'ticketclose';

    /**
     * @var array Command Aliases
     */
    protected $aliases = ['ticketclosecommand'];

    /**
     * @var string Command Description
     */
    protected $description = "закрыть заказ";

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
        $ticket = null;
        try {
            if (preg_match('/\/(\S*)\s(.*)$/', Telegram::getWebhookUpdates()['message']['text'], $matches)) {
                $ticketId = $matches[2];
                $ticket = Ticket::find($ticketId);
                if (empty($ticket) || !$ticket->userAllowed(Auth::user())) {
                    $this->replyWithMessage(['text' => 'Нет доступа либо тикета не существует']);
                    return false;
                }
            } else {
                $this->replyWithMessage(['text' => 'Ошибка ввода']);
                return false;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->replyWithMessage(['text' => 'Непредвиденная ошибка']);
            return false;
        }
        $newState = TicketState::where('is_closed', 1)->first();
        TicketFactory::update($ticket, null, null, null, $newState);
        TicketMessageFactory::build('Тикет закрыт пользователем ' . Auth::user()->name, User::crm(), $ticket);
        $ticketsList = sprintf('%s' . PHP_EOL, 'Доступные тикеты');
        $tickets = TicketRepository::activeTicketsForUser(Auth::user())->filter(function (Ticket $ticket) {
            return !$ticket->currentState()->is_closed;
        });
        foreach ($tickets as $ticket) {
            $ticketsList .= sprintf('[%s: %s (%s)](%s)' . PHP_EOL, $ticket->id, $ticket->name, $ticket->ticketTheme->name, route('tickets.show', ['id' => $ticket->id]));
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
                'text' => '/ticket ' . $ticket->id . ' ' . $ticket->name
            ];
        }
        $buttons = array_chunk($buttons, 2);
        $buttons[] = ['/tickets - все тикеты'];
        $buttons[] = ['/help - помощь'];
        array_unshift($buttons, ['/ticketcreate - Создать тикет']);
        return $buttons;
    }
}