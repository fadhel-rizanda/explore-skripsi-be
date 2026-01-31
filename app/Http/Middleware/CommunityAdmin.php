<?php

namespace App\Http\Middleware;

use App\Traits\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CommunityAdmin
{
    use ResponseAPI;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $community = $request->route()->parameter('community');
            $user = auth('api')->user();

            if (! $user) {
                throw UnauthorizedException::notLoggedIn();
            }

            if ($user->hasRole('admin')) {
                return $next($request);
            }

            $isAdmin = $community->admins()
                ->where('user_id', $user->id)
                ->exists();

            if (! $isAdmin && $community->created_by !== $user->id) {
                return $this->sendError('You do not have admin access to this community.', 403);
            }

            return $next($request);
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while checking community admin access.', 500);
        }
    }
}
