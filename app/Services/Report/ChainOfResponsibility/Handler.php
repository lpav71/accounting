<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\Services\Report\Commands\CommandInterface;

/**
 * Interface Handler
 * @package App\Services\Report\ChainOfResponsibility
 */
interface Handler
{
    /**
     * set next handler in chain
     *
     * @param Handler $handler
     * @return Handler
     */
    public function setNext(Handler $handler): Handler;

    /**
     * process
     *
     * @param CommandInterface $command
     * @return CommandInterface
     */
    public function handle(CommandInterface $command): CommandInterface;

}