<?php

namespace App\Observers;

use App\Exceptions\DoingException;
use App\UtmCampaign;

class UtmCampaignObserver
{
    /**
     * Обработка события 'updating'
     *
     * @param UtmCampaign $utmCampaign
     * @throws DoingException
     * @return false
     */
    public function updating(UtmCampaign $utmCampaign)
    {
        $doingErrors = [
            __('Update operations are not allowed.'),
        ];

        $utmCampaign->syncOriginal();

        DoingException::processErrors($doingErrors);

        return false;
    }
}
