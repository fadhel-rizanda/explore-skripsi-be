<?php

namespace App\Http\Middleware;

use App\Enums\AdoptionStatusEnum;
use App\Enums\RoleEnum;
use App\Traits\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AdoptionStage
{
    use ResponseAPI;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $stages): Response
    {
        $user = auth('api')->user();
        $adoption = $request->route('adoption');

        if (! $user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return $next($request);
        }

        if (! $adoption) {
            return $this->sendError('Adoption not found.', 404);
        }

        if (in_array(
            $adoption->status->name,
            [
                AdoptionStatusEnum::COMPLETED->value,
                AdoptionStatusEnum::REJECTED->value,
                AdoptionStatusEnum::CANCELLED->value,
            ],
            true
        )) {
            return $this->sendError('This adoption application is already finalized.', 403);
        }

        $allowedStages = $this->parseStages($stages);

        if (! in_array($adoption->stageTag->name, $allowedStages, true)) {
            return $this->sendError(
                "Action not allowed at stage '{$adoption->stageTag->name}'.",
                403
            );
        }

        return $next($request);
    }

    protected function parseStages($stages): array
    {
        return is_array($stages)
            ? $stages
            : explode('|', $stages);
    }
}
