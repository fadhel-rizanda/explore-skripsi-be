<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\CreateChatRequest;
use App\Http\Requests\SendMessageRequest;
use App\Models\Chat;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    use ResponseAPI;

    public function getChatRooms()
    {
        $user = auth('api')->user();
        $chatRooms = $user->chatRooms()->with('users', 'lastMessage')->get();

        return $this->sendSuccess('Chat rooms retrieved successfully', $chatRooms);
    }

    public function getMessages($roomId)
    {
        $room = Chat::with(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }, 'messages.user', 'messages.attachment'])->findOrFail($roomId);
        $user = auth('api')->user();

        if (! $room->users()->where('user_id', $user->id)->exists()) {
            return $this->sendError('You are not a member of this chat room', 403);
        }

        return $this->sendSuccess('Messages retrieved successfully', $room->messages);
    }

    public function getOrCreatePrivateChat(CreateChatRequest $request)
    {
        $currentUser = auth('api')->user();
        $userIds = collect($request->user_ids)->push($currentUser->id)->unique()->sort()->values();

        try {
            $chatRoom = Chat::where('type', 'private')
                ->whereHas('users', function ($query) use ($userIds) {
                    $query->whereIn('user_id', $userIds);
                }, '=', count($userIds))
                ->first();
            if (! $chatRoom) {
                $chatRoom = Chat::create([
                    'type' => $request->type,
                    'created_by' => $currentUser->id,
                ]);
                $chatRoom->users()->attach($userIds);
            }

            $chatRoom->load('users', 'lastMessage');

            return $this->sendSuccess('Private chat room retrieved successfully', $chatRoom);
        } catch (\Exception $exception) {
            Log::error('getOrCreatePrivateChat failed: ' . $exception->getMessage(), [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to get private chat', 500);
        }
    }

    public function createChat(CreateChatRequest $request)
    {
        $currentUser = auth('api')->user();
        DB::beginTransaction();

        try {
            $chatRoom = Chat::create([
                'name' => $request->name,
                'type' => $request->type,
                'created_by' => $currentUser->id,
            ]);
            $userIds = collect($request->user_ids)->push($currentUser->id)->unique();
            $chatRoom->users()->attach($userIds);

            $chatRoom->load('users', 'lastMessage');

            DB::commit();

            return $this->sendSuccess('Chat room created successfully', $chatRoom);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('createChat failed: ' . $exception->getMessage(), [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to create chat', 500);
        }
    }

    public function sendMessage($roomId, SendMessageRequest $request)
    {
        $room = Chat::findOrFail($roomId);
        $user = auth('api')->user();

        if (! $room->users()->where('user_id', $user->id)->exists()) {
            return $this->sendError('You are not a member of this chat room', 403);
        }

        try {
            $message = $room->messages()->create([
                'user_id' => $user->id,
                'message' => $request->input('message'),
                'attachment_id' => $request->input('attachment_id'),
            ]);

            $message->load('user', 'attachment');

            broadcast(new MessageSent($message));

            return $this->sendSuccess('Message sent successfully', $message);
        } catch (\Exception $exception) {
            Log::error('sendMessage failed: ' . $exception->getMessage(), [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to send message', 500);
        }
    }

    public function markRoomAsRead($roomId)
    {
        $room = Chat::findOrFail($roomId);
        $user = auth('api')->user();

        if (! $room->users()->where('user_id', $user->id)->exists()) {
            return $this->sendError('You are not a member of this chat room', 403);
        }

        try {
            $room->readStatuses()->updateOrCreate(
                ['user_id' => $user->id],
                ['last_read_at' => now()]
            );

            return $this->sendSuccess('Chat room marked as read');
        } catch (\Exception $exception) {
            Log::error('markRoomAsRead failed: ' . $exception->getMessage(), [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to mark room as read', 500);
        }
    }
}
