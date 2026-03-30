<?php

namespace App\Notifications;

use App\Models\Adoption;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdoptionMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $action,
        protected Adoption $adoption,
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
        return (new MailMessage())
            ->subject("Adoption {$this->action} for {$this->adoption->pet->name}")
            ->view('emails.adoption-action', [
                'adoptionId' => $this->adoption->id,
                'action' => $this->action,
                'petId' => $this->adoption->pet->id,
                'petName' => $this->adoption->pet->name,
                'adopterId' => $this->adoption->adopter->id,
                'adopterName' => $this->adoption->adopter->name,
                'providerId' => $this->adoption->provider->id,
                'providerName' => $this->adoption->provider->name,
                'status' => $this->adoption->status->name,
                'updatedBy' => $this->adoption->updatedBy->name,
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
