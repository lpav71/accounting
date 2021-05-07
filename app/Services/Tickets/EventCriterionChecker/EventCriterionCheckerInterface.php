<?php
declare(strict_types=1);

namespace App\Services\Tickets\EventCriterionChecker;


use App\Ticket;
use App\TicketEventCriterion;
use App\TicketMessage;

/**
 * Interface EventCriterionInterface
 * @package App\Services\Tickets\EventCriteria
 */
interface EventCriterionCheckerInterface
{
    /**
     *
     * @param Ticket $ticket
     * @return $this
     */
    function setTicket(Ticket $ticket): self;

    /**
     * @param TicketMessage $ticketMessage
     * @return $this
     */
    function setMessage(TicketMessage $ticketMessage): self;

    /**
     * текст добавленного сообщения содержит "text"
     *
     * @return bool
     */
    function checkSubstring(): bool;

    /**
     * тема тикета равна какой-то
     *
     * @return bool
     */
    function checkTheme(): bool;

    /**
     * автор тикета такой-то пользователь
     *
     * @return bool
     */
    function checkCreator(): bool;

    /**
     * ответственный тикета такой-то пользователь
     *
     * @return bool
     */
    function checkPerformer(): bool;

    /**
     * срочность тикета такая-то
     *
     * @return bool
     */
    function checkPriority(): bool;

    /**
     * последнее сообщение написано на выбор - автором, ответственным, другим участником
     *
     * @return bool
     */
    function checkLastWriter(): bool;

    /**
     * день недели (можно несколько)
     *
     * @return bool
     */
    function checkWeekday(): bool;

    /**
     * @return bool
     */
    function checkMessagesCount(): bool;

    /**
     * @return bool
     */
    function checkAll(): bool;

    /**
     * @return mixed
     */
    function lastMessageTime(): bool;
    /**
     * @param TicketEventCriterion $ticketEventCriterion
     * @return $this
     */
    function setTicketEventCriterion(TicketEventCriterion $ticketEventCriterion): self;

    /**
     * @return bool
     */
    function checkTicketNameSubstring():bool;
}