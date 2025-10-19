<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckTokenVersion
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Get token from request
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();

            $tokenVersion = $payload->get('token_version', 0);

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'error' => true,
                    'status' => 'error',
                    'message' => 'Unauthorized',
                    'data' => [],
                ], 401);
            }

            if ($tokenVersion !== $user->token_version) {
                return response()->json([
                    'error' => true,
                    'status' => 'error',
                    'message' => 'Token has been invalidated. Please login again.',
                    'data' => [],
                ], 401);
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'status' => 'error',
                'message' => 'Invalid token',
                'data' => [],
            ], 401);
        }
    }
}
