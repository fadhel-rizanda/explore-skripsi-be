<?php

namespace App\Http\Controllers;

use App\Constants\GeneralConfig;
use App\Enums\ActionEnum;
use App\Enums\AttachmentTypeEnum;
use App\Enums\ChatTypeEnum;
use App\Enums\ModelReferenceEnum;
use App\Events\ChatUpdated;
use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
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

        $chatRooms = $user->chatRooms()
            ->with([
                'users' => function ($query) use ($user) {
                    $query->select('mt_user.id', 'name', 'avatar', 'attachment_id')
                        ->where('mt_user.id', '!=', $user->id);
                },
                'users.attachment:id,public_url',
                'lastMessage:id,chat_id,user_id,content,created_at',
            ])
            ->wherePivot('is_active', true)
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
            ->get();

        $chatIds = $chatRooms->pluck('id');

        $userChatRooms = DB::table('tr_chat_room')
            ->whereIn('chat_id', $chatIds)
            ->get(['chat_id', 'user_id', 'is_active', 'last_read_at']);

        $allMemberStatus = $userChatRooms->groupBy('chat_id')
            ->map(fn ($rows) => $rows->pluck('is_active', 'user_id'));

        $allActiveCounts = DB::table('tr_chat_room')
            ->whereIn('chat_id', $chatIds)
            ->where('is_active', true)
            ->groupBy('chat_id')
            ->selectRaw('chat_id, COUNT(*) as count')
            ->pluck('count', 'chat_id');

        $allUnreadCounts = DB::table('tr_message as m')
            ->join('tr_chat_room as cr', function ($join) use ($user) {
                $join->on('m.chat_id', '=', 'cr.chat_id')
                    ->where('cr.user_id', '=', $user->id);
            })
            ->whereIn('m.chat_id', $chatIds)
            ->where('m.user_id', '!=', $user->id)
            ->whereRaw('(cr.last_read_at IS NULL OR m.created_at > cr.last_read_at)')
            ->groupBy('m.chat_id')
            ->selectRaw('m.chat_id, COUNT(*) as count')
            ->pluck('count', 'chat_id');

        $result = $chatRooms->map(function ($chat) use ($allMemberStatus, $allActiveCounts, $allUnreadCounts) {
            $memberStatus = $allMemberStatus[$chat->id] ?? collect();

            $otherUser = $chat->users->first();

            $chatName = ! empty($chat->name) ? $chat->name : (
                $chat->type === ChatTypeEnum::PRIVATE->value && $otherUser
                ? ($otherUser->name ?: $otherUser->email)
                : 'Unknown Chat'
            );

            return [
                'id' => $chat->id,
                'name' => $chatName,
                'type' => $chat->type,
                'description' => $chat->description,
                'users' => $chat->users->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'avatar' => $u->attachment?->public_url ?? $u->avatar,
                    'is_active_member' => (bool) ($memberStatus[$u->id] ?? true),
                ])->values(),
                'active_member_count' => (int) ($allActiveCounts[$chat->id] ?? 0),
                'unread_count' => (int) ($allUnreadCounts[$chat->id] ?? 0),
                'last_message' => $chat->lastMessage ? [
                    'id' => $chat->lastMessage->id,
                    'user_id' => $chat->lastMessage->user_id,
                    'content' => $chat->lastMessage->content,
                    'created_at' => $chat->lastMessage->created_at,
                ] : null,

                'created_by' => $chat->created_by,
            ];
        });

        return $this->sendSuccess('Chat rooms retrieved successfully', $result);
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
                    'updated_at' => $chatMessage->updated_at,
                    'sender' => [
                        'id' => $chatMessage->user->id,
                        'name' => $chatMessage->user->name,
                        'avatar' => $chatMessage->user->attachment?->public_url
                            ?? $chatMessage->user->avatar,
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
        $userIds = collect($request->user_ids)->push($currentUser->id)->unique()->sort()->values()->toArray();
        $userCount = count($userIds);

        $pendingNotification = null;
        $reactivatedUserIds = [];

        try {
            DB::beginTransaction();

            $chatRoom = Chat::where('type', $request->type)
                ->where('name', $request->name)
                ->has('users', '=', $userCount)
                ->where(function ($query) use ($userIds) {
                    foreach ($userIds as $id) {
                        $query->whereHas('users', fn ($q) => $q->where('tr_chat_room.user_id', $id));
                    }
                })
                ->with(['users:id,name'])
                ->first();

            if (! $chatRoom) {
                $chatRoom = Chat::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'type' => $request->type,
                    'created_by' => $currentUser->id,
                ]);

                $chatRoom->users()->attach($userIds);
                $chatRoom->load(['users:id,name']);

                $pendingNotification = $this->notificationService->createBulk(
                    userIds: $userIds,
                    title: 'New Chat Room Initialized',
                    message: 'A new chat room has been initialized.',
                    referenceType: ModelReferenceEnum::CHAT->value,
                    referenceId: $chatRoom->id,
                )->getNotifications()->first();
            }

            $reactivatedUserIds = DB::table('tr_chat_room')
                ->where('chat_id', $chatRoom->id)
                ->where('is_active', false)
                ->pluck('user_id')
                ->toArray();

            if ($chatRoom->type === ChatTypeEnum::PRIVATE->value && empty($chatRoom->name) && ! empty($reactivatedUserIds)) {
                DB::table('tr_chat_room')
                    ->where('chat_id', $chatRoom->id)
                    ->update([
                        'is_active' => true,
                        'updated_at' => now(),
                        'last_read_at' => now(),
                    ]);

                $this->notificationService->createBulk(
                    userIds: $reactivatedUserIds,
                    title: 'Chat Room Reinitialized',
                    message: 'A chat room you are part of has been reinitialized.',
                    referenceType: ModelReferenceEnum::CHAT->value,
                    referenceId: $chatRoom->id,
                );
            }

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('createChat failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to initialize chat', 500);
        }

        if ($pendingNotification) {
            $this->notificationService->broadcast()
                ->notifyUsers(new ChatNotification(
                    action: ActionEnum::CREATED->value,
                    chat: $chatRoom,
                    notes: 'A new chat room has been initialized.'
                ));

            broadcast(new ChatUpdated($pendingNotification));
        }

        if (! empty($reactivatedUserIds)) {
            $this->notificationService->broadcast();
        }

        $data = [
            'id' => $chatRoom->id,
            'type' => $chatRoom->type,
            'name' => $chatRoom->name,
            'description' => $chatRoom->description,
            'created_at' => $chatRoom->created_at,
            'users' => $chatRoom->users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values(),
        ];

        return $this->sendSuccess('Chat room initialized successfully', $data);
    }

    public function sendMessage(Chat $chat, SendMessageRequest $request)
    {
        $user = auth('api')->user();

        $activeMemberCount = DB::table('tr_chat_room')
            ->where('chat_id', $chat->id)
            ->where('is_active', true)
            ->count();

        if ($activeMemberCount < 2) {
            return $this->sendError('This conversation is currently unavailable. Please try again later.', 400);
        }

        try {
            DB::beginTransaction();
            $message = $chat->messages()->create([
                'user_id' => $user->id,
                'content' => $request->input('content'),
                'attachment_id' => $request->input('attachment_id'),
            ]);

            $chat->setAttachmentMetadata(
                attachmentId: $request->input('attachment_id'),
                modelReference: ModelReferenceEnum::MESSAGE->value,
                referenceId: $message->id
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
                'updated_at' => $message->updated_at,
                'sender' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar' => $message->user->attachment?->public_url
                        ?? $message->user->avatar,
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

        try {
            DB::beginTransaction();

            $activeMembers = DB::table('tr_chat_room')
                ->where('chat_id', $chat->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();

            $activeMemberCount = $activeMembers->count();

            $chatDataForNotify = clone $chat;

            if ($activeMemberCount > 1) {
                DB::table('tr_chat_room')
                    ->where('chat_id', $chat->id)
                    ->where('user_id', $user->id)
                    ->update(['is_active' => false]);
            } else {
                $chat->setAttachmentMetadata(
                    attachmentId: null,
                    modelReference: ModelReferenceEnum::CHAT->value,
                );
                $chat->delete();
            }

            DB::commit();

            $this->notificationService->createBulk(
                userIds: [$user->id],
                title: 'Chat Room Deleted',
                message: 'A chat room has been deleted.',
                referenceType: ModelReferenceEnum::CHAT->value,
                referenceId: $chatDataForNotify->id,
            )->broadcast();

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

        if (! $chat->users()->where('mt_user.id', $user->id)->exists()) {
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

            if ($message->created_at->diffInMinutes(now()) > GeneralConfig::MESSAGE_DELETION_WINDOW_MINUTES) {
                return $this->sendError('Message can only be deleted within ' . GeneralConfig::MESSAGE_DELETION_WINDOW_MINUTES . ' minutes of sending', 403);
            }

            if ($message->attachment_id) {
                Attachment::whereId($message->attachment_id)->update([
                    'reference_id' => null,
                    'reference_by' => null,
                    'status' => AttachmentTypeEnum::PENDING->value,
                ]);
            }

            $messageId = $message->id;
            $chatId = $message->chat_id;

            $message->delete();

            broadcast(new MessageDeleted(
                [
                    'id' => $messageId,
                    'sender' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
                $chatId
            ));

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

    public function updateMessage(Chat $chat, Message $message, SendMessageRequest $request)
    {
        $user = auth('api')->user();

        try {
            DB::beginTransaction();

            if ($message->user_id !== $user->id) {
                return $this->sendError('Unauthorized to update this message', 403);
            }

            if ($message->created_at->diffInMinutes(now()) > GeneralConfig::MESSAGE_EDIT_WINDOW_MINUTES) {
                return $this->sendError('Message can only be edited within ' . GeneralConfig::MESSAGE_EDIT_WINDOW_MINUTES . ' minutes of sending', 403);
            }

            $message->update([
                'content' => $request->input('content'),
            ]);

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
                'updated_at' => $message->updated_at,
                'sender' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar' => $message->user->attachment?->public_url
                        ?? $message->user->avatar,
                ],
            ];

            broadcast(new MessageUpdated(
                $data,
                $chat->id
            ));

            DB::commit();

            return $this->sendSuccess('Message updated successfully', $data);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('updateMessage failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to update message', 500);
        }
    }

    public function updateChat(Chat $chat, CreateChatRequest $request)
    {
        $user = auth('api')->user();

        if ((string) $chat->created_by !== (string) $user->id) {
            return $this->sendError('Only the chat creator can update the chat', 403);
        }

        try {
            DB::beginTransaction();

            $chat->update([
                'type' => $request->type,
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $chat->users()->sync(
                collect($request->user_ids)
                    ->push($user->id)
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray()
            );

            DB::commit();

            return $this->sendSuccess('Chat updated successfully', [
                'id' => $chat->id,
                'name' => $chat->name,
                'description' => $chat->description,
                'type' => $chat->type,
                'created_by' => $chat->created_by,
                'updated_by' => $chat->updated_by,
                'created_at' => $chat->created_at,
                'updated_at' => $chat->updated_at,
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('updateChat failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to update chat', 500);
        }
    }
}
