<?php


namespace App\Services\Report\Commands;


use App\Services\Report\ReportMessage;
use App\Services\Report\ReportRequest;

/**
 * command that pass through chain of responsibility
 *
 * Interface CommandInterface
 * @package App\Services\Report\Commands
 */
interface CommandInterface
{

    /**
     * @return ReportRequest
     */
    public function getReportRequest(): ReportRequest;

    /**
     * @return ReportMessage
     */
    public function getReportMessage(): ReportMessage;
}