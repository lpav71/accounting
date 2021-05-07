<?php

namespace App\Observers;

use App\Exceptions\DoingException;
use App\UtmSource;

class UtmSourceObserver
{
    /**
     * Обработка события 'updating'
     *
     * @param UtmSource $utmSource
     * @throws DoingException
     * @return false
     */
    public function updating(UtmSource $utmSource)
    {
        $doingErrors = [
            __('Update operations are not allowed.'),
        ];

        $utmSource->syncOriginal();

        DoingException::processErrors($doingErrors);

        return false;
    }
}
