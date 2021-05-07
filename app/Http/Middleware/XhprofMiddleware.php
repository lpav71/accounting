<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use XHProfRuns_Default;

/**
 * Class XhprofMiddleware.
 *
 * XHProf is a useful profiling tool built by Facebook. This middleware allows
 * developers to profile specific requests by appending `xhprof=true` to any
 * query.
 *
 * Results will be stored on `/tmp` and can be visualized using the XHProf UI.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package App\Http\Middleware
 */
class XhprofMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // We will only profile requests if the proper flag is set on the query
        // of the request. You may further customize this to be disabled on
        // production releases of your application.
        if ($request->query->get('xhprof') !== 'true') {
            return $next($request);
        }

        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

        $result = $next($request);

        $xhprofData = xhprof_disable();
        $xhprofRuns = new XHProfRuns_Default('/tmp');

        $runId = $xhprofRuns->save_run($xhprofData, 'xhprof_laravel');

        // We will attach the XHProf run ID as part of the response header.
        // This is a lot better than modifying the actual response body.
        $result->headers->set('X-Xhprof-Run-Id', $runId);

        return $result;
    }
}