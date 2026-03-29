<?php

namespace App\Notifications;

use App\Models\Chat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $action,
        protected Chat $chat,
        protected ?string $notes = null,
    ) {}

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
    public function toMail($notifiable): MailMessage
    {
        $this->chat->load(['users', 'lastMessage.user']);

        return (new MailMessage())
            ->subject("Chat {$this->action}: " . ($this->chat->name ?? 'Private Chat'))
            ->view('emails.chat-notification', [
                'chatId' => $this->chat->id,
                'chatName' => $this->chat->name,
                'chatType' => $this->chat->type,
                'action' => $this->action,
                'membersCount' => $this->chat->users->count(),
                'lastMessage' => $this->chat->lastMessage?->content,
                'lastMessageBy' => $this->chat->lastMessage?->user?->name,
                'notes' => $this->notes,
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
