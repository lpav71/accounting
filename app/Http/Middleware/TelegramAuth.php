<?php

namespace App\Http\Middleware;

use App\User;
use Closure;

class TelegramAuth
{
    /**
     * Handle an incoming request.
     * Аутентификация пользователя через ID чата если залогинен
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (isset($request['message']['chat']['id'])) {
            $chatId = $request['message']['chat']['id'];
        } elseif (isset($request['callback_query']['message']['chat']['id'])) {
            $chatId = $request['callback_query']['message']['chat']['id'];
        } else {
            $chatId = null;
        }
        $user = User::where('telegram_chat_id', $chatId)->where('is_not_working',0)->first();
        if(!empty($user) && !empty($chatId)){
            \Auth::login($user);
        }
        return $next($request);
    }
}
