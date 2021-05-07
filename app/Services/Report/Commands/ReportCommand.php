<?php


namespace App\Services\Report\Commands;


use App\Services\Report\ReportMessage;
use App\Services\Report\ReportRequest;

/**
 * Class ReportCommand
 * @package App\Services\Report\Commands
 */
class ReportCommand implements CommandInterface
{


    /**
     * @var ReportRequest
     */
    private $reportRequest;

    /**
     * @var ReportMessage
     */
    private $reportMessage;

    /**
     * ReportCommand constructor.
     * @param ReportRequest $reportRequest
     * @param ReportMessage $reportMessage
     */
    public function __construct(ReportRequest $reportRequest, ReportMessage $reportMessage)
    {
        $this->reportRequest = $reportRequest;
        $this->reportMessage = $reportMessage;
    }

    /**
     * @inheritDoc
     */
    public function getReportRequest(): ReportRequest
    {
        return $this->reportRequest;
    }

    /**
     * @inheritDoc
     */
    public function getReportMessage(): ReportMessage
    {
        return $this->reportMessage;
    }
}