<?php

namespace App\Http\Controllers;

use App\Http\Services\NotificationService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResponseAPI;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getNotifications(Request $request)
    {
        $notifications = $this->notificationService->getUserNotifications(
            auth('api')->id(),
            $request->query('unread_only', false),
            $request->query('per_page', 15)
        );

        return $this->sendSuccessPagination('Notifications retrieved successfully', $notifications);
    }

    public function markAsRead($notificationId)
    {
        $notification = $this->notificationService->readNotification(
            auth('api')->id(),
            $notificationId
        );
        if ($notification) {
            return $this->sendSuccess('Notification marked as read', $notification);
        } else {
            return $this->sendError('Notification not found or already read', 404);
        }
    }

    public function markAsUnread($notificationId)
    {
        $notification = $this->notificationService->unreadNotification(
            auth('api')->id(),
            $notificationId
        );
        if ($notification) {
            return $this->sendSuccess('Notification marked as unread', $notification);
        } else {
            return $this->sendError('Notification not found or already read', 404);
        }
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

    public function deleteNotification($notificationId)
    {
        $deleted = $this->notificationService->delete(
            auth('api')->id(),
            $notificationId
        );
        if ($deleted) {
            return $this->sendSuccess('Notification deleted successfully');
        } else {
            return $this->sendError('Notification not found', 404);
        }
    }
}
