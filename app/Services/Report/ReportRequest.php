<?php


namespace App\Services\Report;


use App\OrderDetailState;
use App\OrderState;
use App\TaskState;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class ReportRequest
 * @package App\Services\Report
 */
class ReportRequest
{

    /**
     * @var Carbon $timeFrom
     */
    private $timeFrom;

    /**
     * @var Carbon $timeTo
     */
    private $timeTo;

    /**
     * @var Collection|User[] $users
     */
    private $users;

    /**
     * @var Collection|OrderState[] $orderStates
     */
    private $orderStates;

    /**
     * @var Carbon $confirmTime
     */
    private $confirmTime;

    /**
     * @var Collection|TaskState[] $taskStates
     */
    private $taskStates;

    /**
     * @var Collection|OrderDetailState[] $orderDetailStates
     */
    private $orderDetailStates;

    /**
     * ReportRequest constructor.
     * @param Carbon $timeFrom
     * @param Carbon $timeTo
     * @param Collection $users
     * @param Carbon $confirmTime
     * @param Collection $orderStates
     * @param Collection $taskStates
     */
    public function __construct(Carbon $timeFrom, Carbon $timeTo, Collection $users, Carbon $confirmTime, Collection $orderStates, Collection $taskStates, Collection $orderDetailStates)
    {
        $this->timeFrom = $timeFrom;
        $this->timeTo = $timeTo;
        $this->users = $users;
        $this->confirmTime = $confirmTime;
        $this->orderStates = $orderStates;
        $this->taskStates = $taskStates;
        $this->orderDetailStates = $orderDetailStates;
    }

    /**
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Carbon
     */
    public function getTimeFrom(): Carbon
    {
        return $this->timeFrom;
    }

    /**
     * @return Carbon
     */
    public function getTimeTo(): Carbon
    {
        return $this->timeTo;
    }

    /**
     * @return Carbon
     */
    public function getConfirmTime(): Carbon
    {
        return $this->confirmTime;
    }

    /**
     * @return Collection
     */
    public function getOrderStates(): Collection
    {
        return $this->orderStates;
    }

    /**
     * @return Collection
     */
    public function getTaskStates(): Collection
    {
        return $this->taskStates;
    }

    /**
     * @return Collection
     */
    public function getOrderDetailStates(): Collection
    {
        return $this->orderDetailStates;
    }
}