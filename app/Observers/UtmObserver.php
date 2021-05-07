<?php

namespace App\Observers;

use App\Exceptions\DoingException;
use App\Utm;

class UtmObserver
{
    /**
     * Обработка события 'updating'
     *
     * @param Utm $utm
     * @return false
     * @throws DoingException
     */
    public function updating(Utm $utm)
    {
        $doingErrors = [
            __('Update operations are not allowed.'),
        ];

        $utm->syncOriginal();

        DoingException::processErrors($doingErrors);

        return false;
    }

}
