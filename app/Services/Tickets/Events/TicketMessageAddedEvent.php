<?php
declare(strict_types=1);

namespace App\Services\Tickets\Events;


use App\Services\Tickets\Factories\TicketFactory;
use App\Services\Tickets\Factories\TicketMessageFactory;
use App\TicketState;
use App\User;
use Auth;
use Carbon\Carbon;

class TicketMessageAddedEvent extends AbstractTicketEvent
{
    /**
     *
     */
    function execute(): void
    {
        $users = User::getNotFired();
        foreach ($users as $user) {
            try {
                if ($user->id != $this->user->id && !empty($user->telegram_chat_id) && $this->ticket->userAllowed($user)) {
                    if (\Cache::store('file')->get('current_ticket_' . $user->id) == $this->ticket->id) {
                        //отправить сообщение в открытый чат телеграмма
                        $text = sprintf('%s' . PHP_EOL . '*%s*' . PHP_EOL . '`%s`' . PHP_EOL . PHP_EOL, $this->ticketMessage->user->name, $this->ticketMessage->text, (new Carbon($this->ticketMessage->created_at))->toDateTimeString());
                        \Telegram::bot()->sendMessage([
                            'text' => $text,
                            'parse_mode' => 'Markdown',
                            'chat_id' => $user->telegram_chat_id
                        ]);
                    } elseif ($this->ticket->users->contains($user)) {
                        //если чат не открыт уведомить о сообщении участников чата
                        $text = sprintf('%s' . PHP_EOL . '%s' . PHP_EOL . '*%s*' . PHP_EOL . '`%s`' . PHP_EOL . PHP_EOL, __('New Message in ":ticketName"', ['ticketName' => $this->ticket->name]), $this->ticketMessage->user->name, $this->ticketMessage->text, (new Carbon($this->ticketMessage->created_at))->toDateTimeString());
                        $message = [
                            'text' => $text,
                            'parse_mode' => 'Markdown',
                            'chat_id' => $user->telegram_chat_id
                        ];
                        if (!$this->ticket->currentState()->is_closed) {
                            $message['reply_markup'] = json_encode([
                                'inline_keyboard' => [[
                                    [
                                        'text' => __('Answer'),
                                        'callback_data' => '/answer ' . $this->ticket->id,
                                    ]
                                ]
                                ]
                            ]);
                        }
                        \Telegram::bot()->sendMessage($message);
                    }
                }
            } catch (\Exception $e) {
                \Log::debug($user->id);
                \Log::debug($e->getMessage());
            }

        }
        parent::execute();
    }
}