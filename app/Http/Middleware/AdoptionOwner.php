<?php

namespace App\Http\Middleware;

use App\Traits\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AdoptionOwner
{
    use ResponseAPI;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $adoption = $request->route()->parameter('adoption');
        $user = auth('api')->user();

        if (! $user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if ($adoption->adopter_id !== $user->id && $adoption->provider->id !== $user->id) {
            return $this->sendError('You do not have access to this adoption record.', 403);
        }

        return $next($request);
    }
}
