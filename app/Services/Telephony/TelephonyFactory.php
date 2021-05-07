<?php

namespace App\Services\Telephony;

use App\Services\Telephony\Interfaces\TelephonyFactoryInterface;
use App\Services\Telephony\Factories\BeelineFactory;
use App\Services\Telephony\Factories\MatrixmobileFactory;

/**
 * TelephonyFactory is an abstract factory that makes possible to hide two telephonies under one interface
 *
 * Class TelephonyFactory
 * @package App\Services\Telephony
 */
class TelephonyFactory
{

    /**
     * get Telephony factory
     *
     * @param string $telephony_name
     * @return TelephonyFactoryInterface
     * @throws \Exception
     */
    public function getTelephonyFactory(string $telephony_name): TelephonyFactoryInterface
    {

        switch ($telephony_name) {
            case 'matrixmobile':
                $factory = new MatrixmobileFactory();
                break;

            case 'beeline':
                $factory = new BeelineFactory();
                break;
            default:
                throw new \Exception("Uncnown telephony type [{$telephony_name}]");
        }

        return $factory;
    }
}
