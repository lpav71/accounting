<?php

namespace App\Exceptions;

use Config;
use Exception;

class DoingException extends Exception
{
    /**
     * Массив сообщений об ошибке
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Установка сообщений об ошибке
     *
     * @param array $messages
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;
        $this->message = implode('; ', $messages);
    }

    /**
     * Получение сообщение об ошибке
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Прием массива ошибок и, если он не пустой, возбуждение ошибки
     *
     * @param array $errors
     * @throws DoingException
     */
    public static function processErrors(array $errors)
    {
        if (count($errors)) {

            if(Config::get('exceptions.doing.throw')) {
                $doingException = new self();
                $doingException->setMessages($errors);

                throw $doingException;
            } else {
                Config::set(
                    'exceptions.doing.messages',
                    array_merge(
                        Config::get('exceptions.doing.messages'),
                        $errors
                    )
                );
            }
        }
    }
}
