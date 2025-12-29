<?php

namespace App\Http\Services;

use App\Events\NotificationSent;
use App\Models\Notification;

class NotificationService
{
    public function create(array $data)
    {
        $notification = Notification::create([
            'title' => $data['title'],
            'message' => $data['message'],
            'user_id' => $data['user_id'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
        ]);

        broadcast(new NotificationSent($notification));

        return $notification;
    }

    public function createBulk(array $userIds, array $data)
    {
        $notifications = [];
        foreach ($userIds as $userId) {
            $notifications[] = [
                'id' => \Illuminate\Support\Str::uuid7(),
                'title' => $data['title'],
                'message' => $data['message'],
                'user_id' => $userId,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Notification::insert($notifications);

        foreach ($notifications as $notificationData) {
            $notification = new Notification((array) $notificationData);
            broadcast(new NotificationSent($notification));
        }

        return count($notifications);
    }

    public function getUserNotifications(string $userId, bool $unreadOnly = false, int $perPage = 15)
    {
        $query = Notification::forUser($userId)
            ->latest();

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->paginate($perPage);
    }

    public function getUnreadCount(string $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->count();
    }

    public function readNotification(string $userId, string $notificationId): bool
    {
        return (bool) Notification::forUser($userId)
            ->unread()
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);
    }

    public function unreadNotification(string $userId, string $notificationId): bool
    {
        return (bool) Notification::forUser($userId)
            ->read()
            ->where('id', $notificationId)
            ->update(['read_at' => null]);
    }

    public function markAllAsRead(string $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function markAllAsUnread(string $userId): int
    {
        return Notification::forUser($userId)
            ->read()
            ->update(['read_at' => null]);
    }

    public function delete(string $userId, string $notificationId): bool
    {
        return (bool) Notification::forUser($userId)
            ->where('id', $notificationId)
            ->delete();
    }
}
