<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\WeeklyNotificationReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWeeklyReminderEmail implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $tries = 3;

    public $timeout = 120;

    public $backoff = [60, 120, 240];

    protected string $userId;

    protected int $unreadCount;

    /**
     * Create a new job instance.
     */
    public function __construct(string $userId, int $unreadCount)
    {
        $this->userId = $userId;
        $this->unreadCount = $unreadCount;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);
            if (! $user) {
                return;
            }
            $user->notify(new WeeklyNotificationReminderNotification($this->unreadCount));
        } catch (\Exception $exception) {
            Log::error('Failed to send weekly reminder email to user ID ' . $this->userId . ': ' . $exception->getMessage());

            throw $exception; // di throw lagi biar bisa diretry dan klo udh mentok di handle di failed()
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Weekly reminder job failed permanently', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
