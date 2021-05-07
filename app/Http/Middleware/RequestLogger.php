<?php

namespace App\Http\Middleware;

use Closure;
use File;
use Illuminate\Support\Facades\Auth;
use Storage;
use Illuminate\Support\Carbon;
use Log;

class RequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
        try {
            $data = [
                'user_id' => (int) Auth::id(),
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'],
                'request' => $request->input(),
                'ip' => $_SERVER['REMOTE_ADDR']
            ];
            Log::channel('request_log')->info(json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            report($e);
        }
        return $next($request);
    }
}
