<?php

namespace App\Http\Services;

use App\Events\NotificationSent;
use App\Models\Notification;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationService
{
    protected Collection $notifications;

    public function __construct()
    {
        $this->notifications = collect();
    }

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

    /**
     * ini buat create notifikasi secara bulk, secara default otomatis broadcast notifikasinya bisa di chain lagi buat ngirim email atau lainya
     *
     * @return $this
     */
    public function createBulk(
        array $userIds,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): self {
        $now = now();

        $rows = collect($userIds)->map(fn ($userId) => [
            'id' => Str::uuid7(),
            'title' => $title,
            'message' => $message,
            'user_id' => $userId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Notification::insert($rows->toArray());
        $this->notifications = Notification::whereIn('id', $rows->pluck('id'))->get();

        return $this;
    }

    /**
     * ini buat/nambah broadcast notifikasi yang sudah dibuat secara bulk sebelumnya jadi bisa di chain setelah createBulkNotify
     *
     * @return $this
     */
    public function broadcast(string $eventClass = NotificationSent::class): self
    {
        foreach ($this->notifications as $notification) {
            broadcast(new $eventClass($notification));
        }

        return $this;
    }

    /**
     * ini buat ngirim email notifikasi setelah dibuat secara bulk, notifikasi objnya di clone biar tiap user dapet instance yang beda
     */
    public function notifyUsers(
        BaseNotification $notificationObj
    ): self {
        $this->notifications->loadMissing('user');

        foreach ($this->notifications as $notification) {
            $notification->user->notify(
                clone $notificationObj
            );
        }

        return $this;
    }

    /**
     * ambil collection notifikasi yang sudah dibuat secara bulk
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
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
