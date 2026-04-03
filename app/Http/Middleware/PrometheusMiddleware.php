<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PrometheusMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is(config('prometheus.urls.default', 'prometheus') . '*')) {
            return $next($request);
        }

        Cache::store('redis')->increment('http_requests_count');

        return $next($request);
    }
}
