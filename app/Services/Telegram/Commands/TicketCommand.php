<?php


namespace App\Services\Telegram\Commands;


use App\Ticket;
use Auth;
use Carbon\Carbon;
use Log;
use Telegram;
use Telegram\Bot\Commands\Command;

class TicketCommand extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = 'ticket';

    /**
     * @var array Command Aliases
     */
    protected $aliases = ['ticketcommand'];

    /**
     * @var string Command Description
     */
    protected $description = "Показать конкретный тикет";

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
            if (preg_match('/\/(\S*)\s(\S*)/', Telegram::getWebhookUpdates()['message']['text'], $matches)) {
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
        \Cache::store('file')->put('current_ticket_' . Auth::id(), $ticket->id, 480);
        $text = sprintf('[%s: %s](%s)' . PHP_EOL, $ticket->id, $ticket->name, route('tickets.show', ['id' => $ticket->id]));
        $text .= sprintf('%s: %s' . PHP_EOL, 'Тема', $ticket->ticketTheme->name);
        $text .= sprintf('%s: %s' . PHP_EOL, 'Статус', $ticket->currentState()->name);
        $text .= sprintf('%s: %s' . PHP_EOL, 'Автор', $ticket->creator->name);
        if (isset($ticket->performer)) {
            $text .= sprintf('%s: %s' . PHP_EOL, 'Исполнитель', $ticket->performer->name);
        }
        $text .= sprintf(PHP_EOL);
        for ($i = 0; $i < $ticket->ticketMessages->count(); ) {
            if (strlen($text) > 4096) {
                $this->replyWithMessage([
                    'text' => $text,
                    'parse_mode' => 'Markdown'
                ]);
                $text = '';
            } else {
                $text .= sprintf('%s' . PHP_EOL . '*%s*' . PHP_EOL . '`%s`' . PHP_EOL . PHP_EOL, $ticket->ticketMessages[$i]->user->name, $ticket->ticketMessages[$i]->text, (new Carbon($ticket->ticketMessages[$i]->created_at))->toDateTimeString());
                $i++;
            }
        }
        $keyboard = json_encode([
            'keyboard' => $this->ticketButtons($ticket),
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        $this->replyWithMessage([
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'Markdown'
        ]);


    }
    private function ticketButtons($ticket): array
    {
        $buttons = [];
        $buttons[] = ['/ticketcreate - Создать тикет'];
        $buttons[] = ['/tickets - выйти из тикета'];
        $buttons[] = ['/ticketclose ' . $ticket->id . ' - закрыть тикет'];
        return $buttons;
    }
}