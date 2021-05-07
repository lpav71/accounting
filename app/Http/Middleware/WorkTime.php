<?php

namespace App\Http\Middleware;

use App\User;
use Closure;

/**
 * Посредник для добавления флага необходимости запроса Рабочего табеля в представление
 *
 * @package App\Http\Middleware
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class WorkTime
{
    /**
     * Перехват запроса и добавление флага необходимости запроса Рабочего табеля в представление
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        $isNeedWorkTime =
            !is_null($user)
            && $user instanceof User
            && $user->isTaskPerformer()
            && !$user->isHaveActiveWorkTable();

        \View::share('isNeedWorkTime', $isNeedWorkTime);

        return $next($request);
    }
}
