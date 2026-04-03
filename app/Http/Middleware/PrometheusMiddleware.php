<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PrometheusMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('prometheus*')) {
            return $next($request);
        }

        Cache::increment('http_requests_count');

        return $next($request);
    }
}
