<?php

namespace App\Http\Middleware;

use App\Traits\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostOwner
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
            $post = $request->route()->parameter('post');
            $user = auth('api')->user();

            if (! $user) {
                return $this->sendError('Unauthorized', 401);
            }

            if ($post->created_by !== $user->id) {
                return $this->sendError('You do not have permission to modify this post.', 403);
            }

            return $next($request);
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while checking post owner access.', 500);
        }
    }
}
