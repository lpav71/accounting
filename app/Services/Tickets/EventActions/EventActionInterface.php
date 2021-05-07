<?php
declare(strict_types=1);

namespace App\Services\Tickets\EventActions;


use App\Ticket;
use App\TicketEventAction;
use App\TicketMessage;

/**
 *
 * Interface EventActionInterface
 * @package App\Services\Tickets\EventActions
 */
interface EventActionInterface
{
    /**
     * EventActionInterface constructor.
     * @param Ticket $ticket
     * @param TicketEventAction $action
     * @param TicketMessage $message
     */
    public function __construct(Ticket $ticket, TicketEventAction $action, TicketMessage $message);

    /**
     * @param Ticket $ticket
     * @return $this
     */
    function setTicket(Ticket $ticket): self;

    /**
     * @param TicketMessage $message
     * @return $this
     */
    function setMessage(TicketMessage $message): self;

    /**
     * @param TicketEventAction $action
     * @return $this
     */
    function setAction(TicketEventAction $action): self;

    /**
     *заменить в последнем сообщении тикета фразу "текст1" на "текст2"
     * "текст1=>текст2"
     * @return bool
     */
    function replaceString(): bool;

    /**
     *
     * добавить в чат пользователя такого-то
     * @return bool
     */
    function addUser(): bool;

    /**
     * добавить в чат пользователей из списка при условии что он сейчас работает
     * (у нас менеджеры выбирают время до скольки работают сегодня).
     * Есть список пользователей, добавим того кто работает (одного, первого).
     * Если никто не работает - никого не добавим
     * @return bool
     */
    function addUsers(): bool;

    /**
     * написать в тикет автосообщение
     *
     * @return bool
     */
    function sendMessage(): bool;

    /**
     * изменить срочность тикета
     *
     * @return bool
     */
    function changePriority(): bool;

    /**
     * изменить ответственного по тикету (селект с пользователями).
     * Первый добавленный после автора становится ответственным автоматически
     * @return bool
     */
    function changePerformer(): bool;

    /**
     * отправить повторное уведомление в телегу
     *
     * @return bool
     */
    function sendNotification(): bool;


    /**
     * @return bool
     */
    function execute(): bool;
}