<?php


namespace App\Http\Helpers;


use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Вспомогательный класс для постраничной разбивки
 *
 * @package App\Http\Helpers
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class Paginator
{
    protected function __construct()
    {
    }

    /**
     * Постраничная разбивка коллекции
     *
     * @param Collection $items
     * @param int $perPage
     * @param int $page
     * @param array $options
     * @return LengthAwarePaginator
     */
    public static function paginate(
        Collection $items,
        int $perPage = 15,
        int $page = null,
        array $options = []
    ): LengthAwarePaginator {
        $page = $page ?: (\Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

}
