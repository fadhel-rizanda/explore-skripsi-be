<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\CreateChatRequest;
use App\Http\Requests\SendMessageRequest;
use App\Models\Chat;
use App\Traits\ResponseAPI;

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
    $room = Chat::with(['messages' => function($query) {
        $query->orderBy('created_at', 'asc');
    }, 'messages.user', 'messages.attachment'])->findOrFail($roomId);
    $user = auth('api')->user();

    if(!$room->users->contains($user->id)) {
        return $this->sendError('You are not a member of this chat room', 403);
    }

    return $this->sendSuccess('Messages retrieved successfully', $room->messages);
}

    public function getOrCreatePrivateChat(CreateChatRequest $request)
    {
        $currentUser = auth('api')->user();
        $userIds = collect([$request->user_ids, $currentUser])->sort()->values();
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
    }

    public function createChat(CreateChatRequest $request){
        $currentUser = auth('api')->user();
        $chatRoom = Chat::create([
            'name' => $request->name,
            'type' => $request->type,
            'created_by' => $currentUser->id,
        ]);
        $userIds = collect($request->user_ids)->push($currentUser->id)->unique();
        $chatRoom->users()->attach($userIds);

        $chatRoom->load('users', 'lastMessage');

        return $this->sendSuccess('Chat room created successfully', $chatRoom);
    }

    public function sendMessage($roomId, SendMessageRequest $request)
    {
        $room = Chat::findOrFail($roomId);
        $user = auth('api')->user();

        if(!$room->users->contains($user->id)) {
            return $this->sendError('You are not a member of this chat room', 403);
        }

        $message = $room->messages()->create([
            'user_id' => $user->id,
            'message' => $request->input('message'),
            'attachment_id' => $request->input('attachment_id'),
        ]);

        $message->load('user', 'attachment');

        broadcast(new MessageSent($message));

        return $this->sendSuccess('Message sent successfully', $message);
    }

    public function markRoomAsRead($roomId)
    {
        $room = Chat::findOrFail($roomId);
        $user = auth('api')->user();

        if(!$room->users->contains($user->id)) {
            return $this->sendError('You are not a member of this chat room', 403);
        }

        $room->readStatuses()->updateOrCreate(
            ['user_id' => $user->id],
            ['last_read_at' => now()]
        );

        return $this->sendSuccess('Chat room marked as read');
    }
}
