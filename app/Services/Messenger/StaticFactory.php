<?php
declare(strict_types=1);

namespace App\Services\Messenger;

use App\Services\Messenger\Classes\SmsMessenger;
use App\Services\Messenger\Interfaces\MessengerInterface;
use App\Services\Messenger\Interfaces\MessengerStaticFactoryInterface;
use Exception;

/**
 * Class StaticFactory
 * @package App\Services\Messenger
 */
class StaticFactory implements MessengerStaticFactoryInterface
{
    public static function build(string $type = ''): MessengerInterface
    {
        switch ($type) {
            case 'sms' :
                $messenger = new SmsMessenger();
                break;
            default:
                throw new Exception("Неизвестный тип [${$type}]");
        }

        return $messenger;
    }
}