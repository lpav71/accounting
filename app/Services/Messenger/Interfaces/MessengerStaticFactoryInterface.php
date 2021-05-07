<?php
declare(strict_types=1);

namespace App\Services\Messenger\Interfaces;


/**
 * Interface MessengerStaticFactoryInterface
 * @package App\Services\Messenger\Interfaces
 */
interface MessengerStaticFactoryInterface
{

    /**
     * build messenger by type
     *
     * @param string $type
     * @return MessengerInterface
     */
    public static function build(string $type): MessengerInterface;

}