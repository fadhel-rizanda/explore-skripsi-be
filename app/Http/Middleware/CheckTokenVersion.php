<?php

namespace App\Http\Middleware;

use App\Models\RefreshToken;
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
            $user = auth('api')->user();

            if (! $user || ! $user->is_active) {
                return $this->sendError('Unauthorized', 401);
            }

            $payload = JWTAuth::parseToken()->getPayload();
            $tokenVersionInJwt = $payload->get('token_version');

            if ($tokenVersionInJwt !== $user->token_version) {
                RefreshToken::where('user_id', $user->id)->delete();

                return $this->sendError('Session invalidated. Please login again.', 401);
            }

            return $next($request);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->sendError('Token expired', 401);
        } catch (\Exception $e) {
            return $this->sendError('Invalid token', 401);
        }
    }
}
