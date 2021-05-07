<?php
declare(strict_types=1);

namespace App\Services\Tickets\Events;


use App\Services\Tickets\EventActions\EventAction;
use App\Services\Tickets\EventCriterionChecker\EventCriterionChecker;
use App\Ticket;
use App\TicketEventSubscription;
use App\TicketMessage;
use App\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class AbstractTicketEvent
 * @package App\Services\Tickets\Events
 */
abstract class AbstractTicketEvent implements TicketEventInterface
{

    /**
     * @var Ticket
     */
    protected $ticket;

    /**
     * @var TicketMessage|null
     */
    protected $ticketMessage;

    /**
     * @var User|null
     */
    protected $user;
    /**
     * @inheritDoc
     */
    public function __construct(Ticket $ticket, TicketMessage $ticketMessage = null, User $user = null)
    {
        $this->ticket = $ticket;
        $this->ticketMessage = $ticketMessage;
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    function execute(): void
    {
        $ticketCriteria = $this->getSubscriptions();
        $this->processSubscriptions($ticketCriteria);
    }

    /**
     * @return Collection
     */
    private function getSubscriptions(): Collection
    {
        return TicketEventSubscription::where('event', get_called_class())->get();
    }


    /**
     * @param Collection $subscriptions
     * @return void
     */
    private function processSubscriptions(Collection $subscriptions): void
    {
        foreach ($subscriptions as $subscription) {
            $criteriaPassed = $this->checkAllCriteria($subscription->ticketEventCriteria);
            if ($criteriaPassed) {
                $this->doAllActions($subscription->ticketEventActions);
            }
        }
    }


    /**
     * @param Collection $criteria
     * @return bool
     */
    private function checkAllCriteria(Collection $criteria): bool
    {
        foreach ($criteria as $criterion) {
            $passed = (new EventCriterionChecker($this->ticket, $criterion, $this->ticketMessage))->checkAll();
            if (!$passed) {
                return false;
            }
        }
        return true;
    }


    /**
     * @param Collection $actions
     */
    private function doAllActions(Collection $actions): void
    {
        foreach ($actions as $action) {
            (new EventAction($this->ticket, $action, $this->ticketMessage))->execute();
        }
    }


}