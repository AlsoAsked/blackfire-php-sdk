<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Laravel;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Http\RedirectResponse as LaravelRedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OctaneProfilerMiddleware
{
    private $profiler;

    public function __construct()
    {
        $this->profiler = new OctaneProfiler();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response)  $next
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!class_exists(\BlackfireProbe::class)) {
            return $next($request);
        }

        try {
            $this->profiler->start($request);
            $response = $next($request);

            /** @var int|null */
            $statusCode = null;

            if ($response instanceof SymfonyResponse) {
                $statusCode = $response->getStatusCode();
            } else if ($response instanceof LaravelResponse || $response instanceof LaravelRedirectResponse) {
                $statusCode = $response->status();
            }

            if ($statusCode !== null) {
                \BlackfireProbe::setAttribute('http.status_code', $statusCode);
            }
        } finally {
            $this->profiler->stop($request, $response ?? null);
        }

        return $response;
    }
}
