<?php

namespace App\Http\Controllers;

use App\Enums\ActionEnum;
use App\Enums\AttachmentTypeEnum;
use App\Enums\ChatTypeEnum;
use App\Enums\ModelFlagEnum;
use App\Enums\ModelReferenceEnum;
use App\Events\ChatUpdated;
use App\Events\MessageSent;
use App\Http\Requests\CreateChatRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Services\NotificationService;
use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ChatNotification;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function getChatRooms()
    {
        $user = auth('api')->user();

        //        TODO: Optimalkan query untuk menghitung unread_count
        $chatRooms = $user->chatRooms()
            ->with([
                'users' => function ($query) use ($user) {
                    $query->select('mt_user.id', 'name', 'email', 'avatar', 'attachment_id')
                        ->where('mt_user.id', '!=', $user->id);
                },
                'users.attachment:id,public_url',
                'lastMessage:id,chat_id,user_id,content,created_at',
            ])
            ->leftJoin('tr_message as latest_msg', function ($join) {
                $join->on('mt_chat.id', '=', 'latest_msg.chat_id')
                    ->whereRaw('latest_msg.id = (
                    SELECT id FROM tr_message
                    WHERE chat_id = mt_chat.id
                    ORDER BY created_at DESC
                    LIMIT 1
                )');
            })
            ->orderByRaw('latest_msg.created_at DESC NULLS LAST')
            ->select('mt_chat.*')
            ->get()
            ->map(function ($chat) use ($user) {
                // Hitung unread_count secara manual
                $userChatRoom = DB::table('tr_chat_room')
                    ->where('chat_id', $chat->id)
                    ->where('user_id', $user->id)
                    ->first();

                $unreadCount = 0;
                if ($userChatRoom) {
                    $query = DB::table('tr_message')
                        ->where('chat_id', $chat->id)
                        ->where('user_id', '!=', $user->id); // pesan dari user lain

                    if ($userChatRoom->last_read_at) {
                        $query->where('created_at', '>', $userChatRoom->last_read_at);
                    } else {
                        // Jika belum pernah baca, hitung semua pesan
                        $query->whereNotNull('created_at');
                    }

                    $unreadCount = $query->count();
                }

                $otherUser = $chat->users->first();
                $chatName = ($chat->type === ChatTypeEnum::PRIVATE->value && $otherUser)
                    ? ($otherUser->name ?: $otherUser->email)
                    : $chat->name;

                return [
                    'id' => $chat->id,
                    'name' => $chatName,
                    'type' => $chat->type,

                    'users' => $chat->users
                        ->where('id', '!=', $user->id)
                        ->map(fn ($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'avatar' => $u->avatar ?? $u->attachment?->public_url,
                        ])
                        ->values(),

                    'unread_count' => $unreadCount,

                    'last_message' => $chat->lastMessage ? [
                        'id' => $chat->lastMessage->id,
                        'user_id' => $chat->lastMessage->user_id,
                        'content' => $chat->lastMessage->content,
                        'created_at' => $chat->lastMessage->created_at,
                    ] : null,
                ];
            });

        return $this->sendSuccess('Chat rooms retrieved successfully', $chatRooms);
    }

    public function getMessages(Chat $chat, Request $request)
    {
        $limit = min($request->input('limit', 10), 100);
        $idBefore = $request->input('before_id');
        $chat->load([
            'messages' => function ($q) use ($idBefore, $limit) {
                $q->orderBy('id', 'desc')
                    ->when($idBefore, fn ($q) => $q->where('id', '<', $idBefore))
                    ->limit($limit);
            },
            'messages.user',
            'messages.user.attachment:id,public_url',
            'messages.attachment' => fn ($q) => $q
                ->select('id', 'public_url', 'filename', 'file_size', 'mime_type')
                ->where('status', AttachmentTypeEnum::COMPLETED->value),
        ]);

        $messages = $chat->messages
            ->sortBy('id')
            ->map(function ($chatMessage) {
                return [
                    'id' => $chatMessage->id,
                    'content' => $chatMessage->content,
                    'attachment' => $chatMessage->attachment ? [
                        'id' => $chatMessage->attachment->id,
                        'public_url' => $chatMessage->attachment->public_url,
                        'filename' => $chatMessage->attachment->filename,
                        'file_size' => $chatMessage->attachment->file_size,
                        'mime_type' => $chatMessage->attachment->mime_type,
                    ] : null,
                    'created_at' => $chatMessage->created_at,
                    'sender' => [
                        'id' => $chatMessage->user->id,
                        'name' => $chatMessage->user->name,
                        'email' => $chatMessage->user->email,
                        'avatar' => $chatMessage->user->avatar
                            ?? $chatMessage->user->attachment?->public_url,
                    ],
                ];
            })
            ->sortBy('id')
            ->values();

        return $this->sendSuccess('Messages retrieved successfully', [
            'messages' => $messages,
            'next_cursor' => $messages->first()['id'] ?? null,
            'has_more' => $messages->count() === $limit,
        ]);
    }

    public function createChat(CreateChatRequest $request)
    {
        $currentUser = auth('api')->user();
        $userIds = collect($request->user_ids)
            ->push($currentUser->id)
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        try {
            $chatRoom = Chat::where('type', $request->type)
                ->whereHas('users', function ($query) use ($userIds) {
                    $query->whereIn('user_id', $userIds);
                }, '=', count($userIds))
                ->with(['users', 'lastMessage'])
                ->first();
            if (! $chatRoom) {
                DB::beginTransaction();
                $chatRoom = Chat::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'type' => $request->type,
                    'created_by' => $currentUser->id,
                ]);
                $chatRoom->users()->attach($userIds);

                $notification = $this->notificationService->createBulk(
                    userIds: $request->user_ids,
                    title: 'New Chat Room Created',
                    message: 'A new chat room has been created.',
                    referenceType: ModelFlagEnum::CHAT->value,
                    referenceId: $chatRoom->id,
                )->notifyUsers(
                    new ChatNotification(
                        action: ActionEnum::CREATED->value,
                        chat: $chatRoom,
                        notes: 'A new chat room has been created.'
                    )
                )->getNotifications()->first();
                DB::commit();
                broadcast(new ChatUpdated($notification));
            }

            $data = [
                'id' => $chatRoom->id,
                'type' => $chatRoom->type,
                'users' => $chatRoom->users->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'avatar' => $u->avatar ?? $u->attachment?->public_url,
                ])->values(),
                'name' => $chatRoom->name,
                'created_at' => $chatRoom->created_at,
                'last_message' => $chatRoom->lastMessage ? [
                    'id' => $chatRoom->lastMessage->id,
                    'user_id' => $chatRoom->lastMessage->user_id,
                    'content' => $chatRoom->lastMessage->content,
                    'created_at' => $chatRoom->lastMessage->created_at,
                ] : null,
            ];

            return $this->sendSuccess('Chat room created successfully', $data);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('createChat failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to create chat', 500);
        }
    }

    public function sendMessage(Chat $chat, SendMessageRequest $request)
    {
        $user = auth('api')->user();

        try {
            DB::beginTransaction();
            $message = $chat->messages()->create([
                'user_id' => $user->id,
                'content' => $request->input('content'),
                'attachment_id' => $request->input('attachment_id'),
            ]);

            $chat->setAttachmentMetadata(
                attachmentId: $request->input('attachment_id'),
                modelReference: ModelReferenceEnum::CHAT->value,
            );

            $chat->users()->updateExistingPivot(
                $user,
                ['last_read_at' => now()]
            );
            DB::commit();

            $message->load('user', 'attachment');

            $data = [
                'id' => $message->id,
                'content' => $message->content,
                'attachment' => $message->attachment ? [
                    'id' => $message->attachment->id,
                    'public_url' => $message->attachment->public_url,
                    'filename' => $message->attachment->filename,
                    'file_size' => $message->attachment->file_size,
                    'mime_type' => $message->attachment->mime_type,
                ] : null,
                'created_at' => $message->created_at,
                'sender' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'email' => $message->user->email,
                    'avatar' => $message->user->avatar
                        ?? $message->user->attachment?->public_url,
                ],
            ];
            broadcast(new MessageSent($message));

            return $this->sendSuccess('Message sent successfully', $data);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('sendMessage failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to send message', 500);
        }
    }

    public function deleteChat(Chat $chat)
    {
        $user = auth('api')->user();

        if ($chat->created_by !== $user->id) {
            return $this->sendError('Unauthorized to delete this chat', 403);
        }

        try {
            DB::beginTransaction();

            $chat->setAttachmentMetadata(
                attachmentId: null,
                modelReference: ModelReferenceEnum::CHAT->value,
            );

            $chat->delete();

            DB::commit();

            return $this->sendSuccess('Chat deleted successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('deleteChat failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to delete chat', 500);
        }
    }

    public function leaveChat(Chat $chat)
    {
        $user = auth('api')->user();

        if (!$chat->users()->where('mt_user.id', $user->id)->exists()) {
            return $this->sendError('You are not a member of this chat', 400);
        }

        try {
            DB::beginTransaction();

            $chat->users()->detach($user->id);

            if ($chat->users()->count() === 0) {
                $chat->setAttachmentMetadata(null, ModelReferenceEnum::CHAT->value);
                $chat->delete();
            }

            DB::commit();
            return $this->sendSuccess('Left chat successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('leaveChat failed', ['error' => $exception->getMessage()]);
            return $this->sendError('Failed to leave chat', 500);
        }
    }

    public function removeUserFromChat(Chat $chat, User $user)
    {
        $authUser = auth('api')->user();

        if ((string) $chat->created_by !== (string) $authUser->id) {
            return $this->sendError('Only the chat creator can kick members', 403);
        }

        if ((string) $user->id === (string) $authUser->id) {
            return $this->sendError('You cannot kick yourself. Use the leave feature instead.', 400);
        }

        try {
            DB::beginTransaction();
            $chat->users()->detach($user->id);
            DB::commit();

            return $this->sendSuccess("User {$user->name} kicked successfully");
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('kickUserFromChat failed', ['error' => $exception->getMessage()]);
            return $this->sendError('Failed to kick user', 500);
        }
    }
    public function deleteMessage(Chat $chat, Message $message)
    {
        $user = auth('api')->user();

        try {
            DB::beginTransaction();

            if ($message->user_id !== $user->id) {
                return $this->sendError('Unauthorized to delete this message', 403);
            }

            if ($message->attachment_id) {
                Attachment::whereId($message->attachment_id)->update([
                    'reference_id' => null,
                    'reference_by' => null,
                    'status' => AttachmentTypeEnum::PENDING->value,
                ]);
            }

            $message->delete();

            DB::commit();

            return $this->sendSuccess('Message deleted successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('deleteMessage failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to delete message', 500);
        }
    }

    public function markRoomAsRead(Chat $chat)
    {
        $userId = auth('api')->id();

        try {
            $chat->users()->updateExistingPivot(
                $userId,
                ['last_read_at' => now()]
            );

            return $this->sendSuccess('Chat room marked as read');
        } catch (\Exception $exception) {
            Log::error('markRoomAsRead failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to mark room as read', 500);
        }
    }
}
