<?php

namespace App\Console\Commands;

use App\OrderDetailState;
use App\OrderState;
use App\Services\Report\ChainOfResponsibility\CarrierTomorrowHandler;
use App\Services\Report\ChainOfResponsibility\OrderDetailStatesHandler;
use App\Services\Report\ChainOfResponsibility\OrderStatesHandler;
use App\Services\Report\ChainOfResponsibility\ReassignTasksHandler;
use App\Services\Report\ChainOfResponsibility\TaskStatesHandler;
use App\Services\Report\ChainOfResponsibility\TelephonyHandler;
use App\Services\Report\ChainOfResponsibility\UncompletedTasks;
use App\Services\Report\ChainOfResponsibility\UnpackedConfirmedOrders;
use App\Services\Report\Commands\ReportCommand;
use App\Services\Report\ReportMessage;
use App\Services\Report\ReportRequest;
use App\TaskState;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Class DayReport
 * @package App\Console\Commands
 */
class DayReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'day:report
                                {--users=* : coma is separator} 
                                {--timeFrom= : Y-m-d date} 
                                {--timeTo= : Y-m-d date}
                                {--confirmTime= : H:i time}
                                {--orderStates=* : coma is separator}
                                {--taskStates=* : coma is separator}
                                {--orderDetailStates=* : coma is separator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $reportRequest = new ReportRequest($this->getTimeFrom(), $this->getTimeTo(), $this->getUsers(), $this->getConfirmTime(), $this->getOrderStates(), $this->getTaskStates(), $this->getOrderDetailStates());
        $command = new ReportCommand($reportRequest, new ReportMessage());

        $orderStateHandler = new OrderStatesHandler();

        $taskStateHandler = new TaskStatesHandler();
        $orderStateHandler->setNext($taskStateHandler);

        $reassignTasksHandler = new ReassignTasksHandler();
        $taskStateHandler->setNext($reassignTasksHandler);

        $uncompletedTasks = new UncompletedTasks();
        $reassignTasksHandler->setNext($uncompletedTasks);

        $unpackedConfirmed = new UnpackedConfirmedOrders();
        $uncompletedTasks->setNext($unpackedConfirmed);

        $orderDetailStates = new OrderDetailStatesHandler();
        $unpackedConfirmed->setNext($orderDetailStates);

        $telephony = new TelephonyHandler();
        $orderDetailStates->setNext($telephony);

        $carrierTomorrow = new CarrierTomorrowHandler();
        $telephony->setNext($carrierTomorrow);

        $commandEnd = $orderStateHandler->handle($command);
        $this->info($commandEnd->getReportMessage()->getText());

        return true;
    }

    /**
     * @return Collection
     */
    private function getUsers(): Collection
    {
        return User::whereIn('id', $this->option('users'))->get();
    }

    /**
     * @return Carbon
     */
    private function getTimeFrom(): Carbon
    {
        return Carbon::parse($this->option('timeFrom'))->setTime(0, 0);
    }

    /**
     * @return Carbon
     */
    private function getTimeTo(): Carbon
    {
        return Carbon::parse($this->option('timeTo'))->setTime(23, 59);
    }

    /**
     * @return Carbon
     */
    private function getConfirmTime(): Carbon
    {
        return Carbon::parse($this->option('confirmTime'));
    }

    /**
     * @return Collection
     */
    private function getOrderStates(): Collection
    {
        return OrderState::whereIn('id', $this->option('orderStates'))->get();
    }

    /**
     * @return Collection
     */
    private function getTaskStates(): Collection
    {
        return TaskState::whereIn('id', $this->option('taskStates'))->get();
    }

    private function getOrderDetailStates(): Collection
    {
        return OrderDetailState::whereIn('id', $this->option('orderDetailStates'))->get();
    }
}
