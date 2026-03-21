<?php

namespace App\Http\Controllers;

use App\Http\Services\NotificationService;
use App\Models\Notification;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function getNotifications(Request $request)
    {
        $userId = auth('api')->id();
        $notifications = Notification::where('user_id', $userId)
            ->when($request->boolean('unread_only'), function ($query) {
                $query->whereNull('read_at');
            })
            ->orderByDesc('created_at')
            ->simplePaginate($request->query('per_page', 15));

        $unreadCount = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return $this->sendSuccessPagination('Notifications retrieved successfully', $notifications, null, 200, ['unread_count' => $unreadCount]);
    }

    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== auth('api')->id()) {
            return $this->sendError('Notification not found', 404);
        }

        if (! $notification->read_at) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return $this->sendSuccess('Notification marked as read');
    }

    public function markAsUnread(Notification $notification)
    {
        if ($notification->user_id !== auth('api')->id()) {
            return $this->sendError('Notification not found', 404);
        }

        if ($notification->read_at) {
            $notification->update([
                'read_at' => null,
            ]);
        }

        return $this->sendSuccess('Notification marked as unread');
    }

    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead(
            auth('api')->id()
        );

        return $this->sendSuccess('All notifications marked as read', ['marked_count' => $count]);
    }

    public function markAllAsUnread()
    {
        $count = $this->notificationService->markAllAsUnread(
            auth('api')->id()
        );

        return $this->sendSuccess('All notifications marked as unread', ['marked_count' => $count]);
    }

    public function deleteNotification(Notification $notification)
    {
        if ($notification->user_id !== auth('api')->id()) {
            return $this->sendError('Notification not found', 404);
        }

        $notification->delete();

        return $this->sendSuccess('Notification deleted successfully');
    }
}
