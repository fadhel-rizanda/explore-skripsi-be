<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class WeeklyNotificationReminderNotification extends Notification
{
    use Queueable, SerializesModels;

    public User $user;

    public int $unreadCount;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, int $unreadCount)
    {
        $this->user = $user;
        $this->unreadCount = $unreadCount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Weekly Notification Reminder')
            ->view('emails.weekly-notification-reminder', [ // param dikirim ke file email blade
                'userName' => $this->user->name ?? $this->user->email,
                'unreadCount' => $this->unreadCount,
                'notificationUrl' => url('/notifications'),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
