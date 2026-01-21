<?php

namespace App\Http\Middleware;

use App\Traits\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckTokenVersion
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
            // Get token from request
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();

            $tokenVersion = $payload->get('token_version', 0);

            $user = auth('api')->user();

            if (! $user) {
                return $this->sendError('Unauthorized', 401);
            }

            if (! $user->is_active) {
                return $this->sendError('User account is deactivated.', 403);
            }

            if ($tokenVersion !== $user->token_version) {
                return $this->sendError('Token has been invalidated. Please login again.', 401);
            }

            return $next($request);
        } catch (\Exception $e) {
            return $this->sendError('Invalid token', 401);
        }
    }
}
