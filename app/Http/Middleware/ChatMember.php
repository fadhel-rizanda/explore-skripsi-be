<?php

namespace App\Http\Middleware;

use App\Traits\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class ChatMember
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
            $chat = $request->route()->parameter('chat');
            $user = auth('api')->user();
            if (! $user) {
                throw UnauthorizedException::notLoggedIn();
            }
            $isMember = $chat->users()
                ->where('mt_user.id', $user->id)
                ->wherePivot('is_active', true)
                ->exists();
            if (! $isMember) {
                return $this->sendError('You are not a member of this chat room.', 403);
            }

            return $next($request);
        } catch (\Exception $exception) {
            return $this->sendError('An error occurred while checking chat room membership.', 500);
        }
    }
}
