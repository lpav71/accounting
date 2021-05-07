<?php
declare(strict_types=1);

namespace App\Services\Tickets\EventCriterionChecker;


use App\Ticket;
use App\TicketEventCriterion;
use App\TicketMessage;
use Carbon\Carbon;

class EventCriterionChecker implements EventCriterionCheckerInterface
{

    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * @var TicketMessage
     */
    private $message;

    /**
     * @var TicketEventCriterion
     */
    private $criterion;

    /**
     * EventCriterionChecker constructor.
     *
     * @param Ticket $ticket
     * @param TicketEventCriterion $ticketEventCriterion
     * @param TicketMessage|null $message
     */
    public function __construct(Ticket $ticket, TicketEventCriterion $ticketEventCriterion, TicketMessage $message = null)
    {
        $this->setTicket($ticket);
        $this->setTicketEventCriterion($ticketEventCriterion);

        if (!empty($message)) {
            $this->setMessage($message);
        }
    }

    /**
     * @inheritDoc
     */
    function setTicket(Ticket $ticket): EventCriterionCheckerInterface
    {
        $this->ticket = $ticket;
        return $this;
    }

    /**
     * @inheritDoc
     */
    function setTicketEventCriterion(TicketEventCriterion $ticketEventCriterion): EventCriterionCheckerInterface
    {
        $this->criterion = $ticketEventCriterion;
        return $this;
    }

    /**
     * @inheritDoc
     */
    function setMessage(TicketMessage $ticketMessage): EventCriterionCheckerInterface
    {
        $this->message = $ticketMessage;
        return $this;
    }

    /**
     * @inheritDoc
     */
    function checkAll(): bool
    {
        if (!$this->checkSubstring()) {
            return false;
        }
        if (!$this->checkTheme()) {
            return false;
        }
        if (!$this->checkCreator()) {
            return false;
        }
        if (!$this->checkPerformer()) {
            return false;
        }
        if (!$this->checkPriority()) {
            return false;
        }
        if (!$this->checkLastWriter()) {
            return false;
        }
        if (!$this->checkWeekday()) {
            return false;
        }
        if (!$this->checkMessagesCount()) {
            return false;
        }
        if (!$this->lastMessageTime()){
            return false;
        }
        if(!$this->checkTicketNameSubstring()){
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    function checkSubstring(): bool
    {
        if (empty($this->criterion->message_substring)) {
            return true;
        }
        if (empty($this->message)) {
            return false;
        }
        return mb_strripos($this->message->text, $this->criterion->message_substring, 0, 'utf-8') !== false;
    }

    /**
     * @inheritDoc
     */
    function checkTheme(): bool
    {
        if (empty($this->criterion->ticket_theme_id)) {
            return true;
        }
        return $this->ticket->ticket_theme_id == $this->criterion->ticket_theme_id;
    }

    /**
     * @inheritDoc
     */
    function checkCreator(): bool
    {
        if (empty($this->criterion->creator_user_id)) {
            return true;
        }
        return $this->criterion->creator_user_id == $this->ticket->creator_user_id;
    }

    /**
     * @inheritDoc
     */
    function checkPerformer(): bool
    {
        if (empty($this->criterion->performer_user_id)) {
            return true;
        }
        return $this->criterion->performer_user_id == $this->ticket->performer_user_id;
    }

    /**
     * @inheritDoc
     */
    function checkPriority(): bool
    {
        if (empty($this->criterion->ticket_priority_id)) {
            return true;
        }
        return $this->criterion->ticket_priority_id == $this->ticket->ticket_priority_id;
    }

    /**
     * @inheritDoc
     */
    function checkLastWriter(): bool
    {
        if (empty($this->criterion->last_writer)) {
            return true;
        }
        if (empty($this->ticket->getLastMessage())) {
            return false;
        }
        $role = $this->ticket->getChatRole($this->ticket->getLastMessage()->user);
        if (!$role) {
            return false;
        }
        return $role == $this->criterion->last_writer;
    }

    /**
     * @inheritDoc
     */
    function checkWeekday(): bool
    {
        if ($this->criterion->weekdays->isEmpty()) {
            return true;
        }
        foreach ($this->criterion->weekdays as $weekday) {
            $isDayOfWeek = 'is' . $weekday->name;
            if (Carbon::now()->$isDayOfWeek()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    function checkMessagesCount(): bool
    {
        if (empty($this->criterion->messages_count)) {
            return true;
        }
        return $this->ticket->ticketMessages->count() == $this->criterion->messages_count;
    }

    /**
     * @inheritDoc
     */
    function lastMessageTime(): bool
    {
        if (empty($this->criterion->last_message_time)) {
            return true;
        }
        if (empty($this->ticket->getLastMessage())) {
            return false;
        }
        $diffInMinutes = Carbon::now()->diffInMinutes($this->ticket->getLastMessage()->created_at);

        return $diffInMinutes == $this->criterion->last_message_time;
    }

    /**
     * @inheritDoc
     */
    function checkTicketNameSubstring(): bool
    {
        if(empty($this->criterion->ticket_name_substring)){
            return true;
        }
        return mb_strripos($this->ticket->name, $this->criterion->ticket_name_substring, 0, 'utf-8') !== false;
    }
}