<?php

namespace App\Http\Controllers;

use App\Services\Tickets\Factories\TicketFactory;
use App\Services\Tickets\Factories\TicketMessageFactory;
use App\Ticket;
use App\TicketPriority;
use App\TicketTheme;
use Auth;
use Cache;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Telegram;

class TelegramWebhookController extends Controller
{

    public function __construct()
    {
        $this->middleware('telegram.auth');
    }

    /**
     * @return string
     */
    public function webhook()
    {
        try {
            if (Auth::check()) {
                //process inline buttons
                if (isset(Telegram::getWebhookUpdates()['callback_query'])) {
                    if (preg_match_all('/\/answer[\s](\S*)/', Telegram::getWebhookUpdates()['callback_query']['data'], $matches)) {
                        //обработка callback_query ответить после уведомления
                        $ticketId = $matches[1][0];
                        Cache::store('file')->put('current_answer_' . Auth::id(), (int)$ticketId, 480);
                        $ticket = Ticket::find($ticketId);
                        $keyboard = json_encode([
                            'keyboard' => $this->ticketButtons($ticket),
                            'resize_keyboard' => true,
                            'one_time_keyboard' => true
                        ]);
                        $text = sprintf('%s' . PHP_EOL, __('Enter message to ":ticket"', ['ticket' => Ticket::find($ticketId)->name]));
                        Telegram::bot()->sendMessage([
                            'text' => $text,
                            'parse_mode' => 'Markdown',
                            'chat_id' => Auth::user()->telegram_chat_id,
                            'reply_markup' => $keyboard
                        ]);
                    } elseif (isset(Telegram::getWebhookUpdates()['callback_query']) && preg_match_all('/\/ticketcreate[\s](\S*)$/', Telegram::getWebhookUpdates()['callback_query']['data'], $matches)) {
                        //обработка callback_query создание тикета - выбрана тема
                        $themeId = (int)$matches[1][0];
                        try {
                            $client = new Client(['base_uri' => 'https://api.telegram.org/bot' . config('telegram.bots.accounting_bot.token') . '/']);
                            $client->request('POST',
                                'editMessageText'
                                ,
                                [
                                    'query' => [
                                        'text' => sprintf('%s' . PHP_EOL . '%s',
                                            __('Theme is:') . ' ' . TicketTheme::find($themeId)->name,
                                            __('Select priority')
                                        ),
                                        'parse_mode' => 'Markdown',
                                        'chat_id' => Auth::user()->telegram_chat_id,
                                        'message_id' => Telegram::getWebhookUpdates()['callback_query']['message']['message_id'],
                                        'reply_markup' => json_encode([
                                            'inline_keyboard' => $this->getButtonsTheme($themeId)
                                        ])
                                    ]
                                ]);
                        } catch (\Exception $e) {
                            \Log::error($e->getMessage());
                        }
                    } elseif (isset(Telegram::getWebhookUpdates()['callback_query']) && preg_match_all('/\/ticketcreate[\s](\S*)[\s](\S*)$/', Telegram::getWebhookUpdates()['callback_query']['data'], $matches)) {
                        //обработка callback_query создание тикета - выбрана тема и приоритет
                        $themeId = (int)$matches[1][0];
                        $priorityId = (int)$matches[2][0];
                        $ticket = TicketFactory::buildByIds(Auth::id(), $priorityId, $themeId);
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
                                Telegram::bot()->sendMessage([
                                    'text' => $text,
                                    'parse_mode' => 'Markdown',
                                    'chat_id' => Auth::user()->telegram_chat_id
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
                        Telegram::bot()->sendMessage([
                            'text' => $text,
                            'reply_markup' => $keyboard,
                            'parse_mode' => 'Markdown',
                            'chat_id' => Auth::user()->telegram_chat_id
                        ]);
                    } else {

                    }
                } else {
                    $message = Telegram::getWebhookUpdates()['message']['text'];
                    $matches = null;
                    $command = null;
                    if (preg_match_all('/\/(\S*)/', $message, $matches)) {
                        $command = $matches[1][0];
                    }
                    //save message if current ticket set and user has access to ticket
                    if (is_null($command)) {
                        if (!empty(Cache::store('file')->get('current_ticket_' . Auth::id())) || !empty(Cache::store('file')->get('current_answer_' . Auth::id()))) {
                            if (!empty(Cache::store('file')->get('current_answer_' . Auth::id()))) {
                                $ticket = Ticket::find(Cache::store('file')->get('current_answer_' . Auth::id()));
                                Cache::store('file')->forget('current_answer_' . Auth::id());
                                \Cache::store('file')->put('current_ticket_' . Auth::id(), $ticket->id, 480);

                                Telegram::bot()->sendMessage([
                                    'text' => __('Sent'),
                                    'parse_mode' => 'Markdown',
                                    'chat_id' => Auth::user()->telegram_chat_id
                                ]);
                            } elseif (Cache::store('file')->get('current_ticket_' . Auth::id())) {
                                $ticket = Ticket::find(Cache::store('file')->get('current_ticket_' . Auth::id()));
                            }
                            if (empty($ticket) || !$ticket->userAllowed(Auth::user())) {
                                $this->replyWithMessage(['text' => 'Нет доступа либо тикета не существует']);
                                Cache::store('file')->forget('current_answer_' . Auth::id());
                                return false;
                            }
                            TicketMessageFactory::build($message, Auth::user(), $ticket);
                        }
                    } else {
                        Cache::store('file')->forget('current_ticket_' . Auth::id());
                        Cache::store('file')->forget('current_answer_' . Auth::id());
                    }
                }
            }

            $update = Telegram::commandsHandler(true);
        } catch (Exception $e) {
            \Log::debug($e->getMessage());
        }
        return 'ok';
    }


    /**
     * получить кнопки пир известной теме тикета
     *
     * @param int $themeID
     * @return array
     */
    private function getButtonsTheme(int $themeID): array
    {
        $buttons = [];
        foreach (TicketPriority::all() as $ticketPriority) {
            $button = [
                'text' => $ticketPriority->name,
                'callback_data' => '/ticketcreate ' . $themeID . ' ' . $ticketPriority->id,
            ];
            $buttons[] = [$button];
        };
        return $buttons;
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
