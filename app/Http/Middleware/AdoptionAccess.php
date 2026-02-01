<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Traits\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AdoptionAccess
{
    use ResponseAPI;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roles = null): Response
    {
        $user = auth('api')->user();

        if (! $user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return $next($request);
        }

        $adoption = $request->route('adoption');

        if (! $adoption) {
            return $this->sendError('Adoption not found.', 404);
        }

        if (! $this->userHasAccess($user, $adoption, $roles)) {
            return $this->sendError(
                'You do not have permission to access this adoption application.',
                403
            );
        }

        return $next($request);
    }

    private function userHasAccess($user, $adoption, $roles): bool
    {
        $allowedRoles = $roles ? explode('|', $roles) : ['adopter', 'provider'];

        $isAdopter = in_array('adopter', $allowedRoles, true)
            && $adoption->adopter_id === $user->id;

        $isProvider = in_array('provider', $allowedRoles, true)
            && $adoption->provider_id === $user->id;

        return $isAdopter || $isProvider;
    }
}
