<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

class RedirectIfEmptyUsers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (User::all()->isEmpty()) {
            return redirect(route('register'));
        }

        return $next($request);
    }
}
