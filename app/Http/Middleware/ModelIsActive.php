<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Traits\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModelIsActive
{
    use ResponseAPI;

    public function handle(Request $request, Closure $next, string $models): Response
    {
        $user = auth('api')->user();
        $modelNames = explode('|', $models);

        if ($user && $user->hasRole(RoleEnum::ADMIN->value)) {
            return $next($request);
        }

        foreach ($modelNames as $modelName) {
            $model = $request->route($modelName);

            if ($model && method_exists($model, 'getAttribute') && ! $model->is_active) {
                return $this->sendError(
                    ucfirst($modelName) . ' not found.',
                    404
                );
            }
        }

        return $next($request);
    }
}
