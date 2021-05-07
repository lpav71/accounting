<?php

namespace App\Services\Report\ChainOfResponsibility;


use App\Services\Report\Commands\CommandInterface;

abstract class AbstractHandler implements Handler
{
    /**
     * @var Handler
     */
    protected $nextHandler;

    /**
     * @inheritDoc
     */
    public function setNext(Handler $handler): Handler
    {
        $this->nextHandler = $handler;

        return $handler;
    }

    /**
     * @inheritDoc
     */
    public function handle(CommandInterface $command): CommandInterface
    {
        $command = $this->addInfo($command);
        if ($this->nextHandler) {
            return $this->nextHandler->handle($command);
        }

        return $command;
    }

    /**
     * add info to command message
     *
     * @param CommandInterface $command
     * @return CommandInterface
     */
    abstract public function addInfo(CommandInterface $command): CommandInterface;

}