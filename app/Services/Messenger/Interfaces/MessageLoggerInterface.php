<?php
declare(strict_types=1);

namespace App\Services\Messenger\Interfaces;


/**
 * logger saves messengers send history
 *
 * Interface MessageLoggerInterface
 * @package App\Services\Messenger\Interfaces
 */
interface MessageLoggerInterface
{

    /**
     * save message to database
     *
     * @return bool
     */
    function save(): bool;

}