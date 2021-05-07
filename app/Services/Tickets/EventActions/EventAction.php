<?php
declare(strict_types=1);

namespace App\Services\Tickets\EventActions;


use App\Services\Tickets\Factories\TicketMessageFactory;
use App\Ticket;
use App\TicketEventAction;
use App\TicketMessage;
use App\User;
use Auth;
use Carbon\Carbon;
use Clue\React\Buzz\Message\MessageFactory;
use Telegram;

class EventAction implements EventActionInterface
{

    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * @var TicketEventAction
     */
    private $action;

    /**
     * @var TicketMessage|null
     */
    private $message;

    /**
     * @inheritDoc
     */
    public function __construct(Ticket $ticket, TicketEventAction $action, TicketMessage $message = null)
    {
        $this->setTicket($ticket);
        $this->setAction($action);
        if (!is_null($message)) {
            $this->setMessage($message);
        }
    }

    /**
     * @inheritDoc
     */
    function setTicket(Ticket $ticket): EventActionInterface
    {
        $this->ticket = $ticket;
        return $this;
    }

    /**
     * @inheritDoc
     */
    function setAction(TicketEventAction $action): EventActionInterface
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @inheritDoc
     */
    function setMessage(TicketMessage $message): EventActionInterface
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @inheritDoc
     */
    function execute(): bool
    {
        $this->replaceString();
        $this->addUser();
        $this->addUsers();
        $this->sendMessage();
        $this->changePriority();
        $this->changePerformer();
        $this->sendNotification();

        return true;
    }

    /**
     * @inheritDoc
     */
    function replaceString(): bool
    {
        if (empty($this->message)) {
            return false;
        }
        if (empty($this->action->message_replace)) {
            return true;
        }
        $find = explode('=>', $this->action->message_replace)[0];
        $replace = explode('=>', $this->action->message_replace)[1];
        if (mb_strripos($this->message->text, $find, 0, 'utf-8') === false) {
            return false;
        }
        $this->message->text = str_replace($find, $replace, $this->message->text);

        return $this->message->save();
    }

    /**
     * @inheritDoc
     */
    function addUser(): bool
    {
        if (empty($this->action->user)) {
            return true;
        }
        $user = $this->action->user;
        if (!empty($user->alternateUser)) {
            $user = $user->alternateUser;
        }
        if (empty($this->ticket->performer_user_id)) {
            $this->ticket->setPerformer($user);
        }
        return $this->ticket->addUser($user);
    }

    /**
     * @inheritDoc
     */
    function addUsers(): bool
    {
        if ($this->action->users->isEmpty()) {
            return true;
        }
        foreach ($this->action->users as $user) {
            if (!empty($user->alternateUser)) {
                $user = $user->alternateUser;
            }
            if ($user->isWorkingNow()) {
                if (empty($this->ticket->performer_user_id)) {
                    $this->ticket->setPerformer($user);
                }
                return $this->ticket->addUser($user);
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    function sendMessage(): bool
    {
        if (empty($this->action->auto_message)) {
            return true;
        }
        $message = new TicketMessage([
                'text' => $this->action->auto_message,
                'user_id' => User::crm()->id,
                'ticket_id' => $this->ticket->id
            ]
        );
        $users = User::getNotFired();
        foreach ($users as $user) {
            if ($user->id != Auth::id() && !empty($user->telegram_chat_id) && \Cache::store('file')->get('current_ticket_' . $user->id) == $this->ticket->id && $this->ticket->userAllowed($user)) {
                $text = sprintf('%s' . PHP_EOL . '*%s*' . PHP_EOL . '`%s`' . PHP_EOL . PHP_EOL, $message->user->name, $message->text, (new Carbon($message->created_at))->toDateTimeString());
                \Telegram::bot()->sendMessage([
                    'text' => $text,
                    'parse_mode' => 'Markdown',
                    'chat_id' => $user->telegram_chat_id
                ]);
            }

        }
        return $message->save();
    }

    /**
     * @inheritDoc
     */
    function changePriority(): bool
    {
        if (empty($this->action->ticket_priority_id)) {
            return true;
        }

        $this->ticket->ticket_priority_id = $this->action->ticket_priority_id;

        return $this->ticket->save();
    }

    /**
     * @inheritDoc
     */
    function changePerformer(): bool
    {
        if (empty($this->action->user)) {
            return true;
        }
        $this->ticket->addUser($this->action->user);
        return $this->ticket->setPerformer($this->action->user);
    }

    /**
     * @inheritDoc
     */
    function sendNotification(): bool
    {
        if (empty($this->action->notify)) {
            return true;
        }
        foreach($this->ticket->users as $ticketUser){
            if(($this->ticket->getChatRole($ticketUser) == $this->action->notify || $this->action->notify == 'ALL') && !empty($ticketUser->telegram_chat_id) && $ticketUser->id != Auth::id()){
                Telegram::bot()->sendMessage([
                    'chat_id' => $ticketUser->telegram_chat_id,
                    'text' => 'Новое событие в тикете /ticket '.$this->ticket->id,
                    'parse_mode' => 'Markdown'
                ]);
            }
        }
        return true;
    }


}