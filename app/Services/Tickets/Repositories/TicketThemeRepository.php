<?php
declare(strict_types=1);

namespace App\Services\Tickets\Repositories;


use App\TicketTheme;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class TicketThemeRepository
 * @package App\Services\Tickets\Repositories
 */
class TicketThemeRepository
{

    /**
     * @return Collection
     */
    public static function all(): Collection
    {
        return TicketTheme::all();
    }

}