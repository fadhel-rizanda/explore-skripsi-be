<?php

namespace App\Jobs;

use App\Http\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

// TODO: Refactor to use CreateNotificationJob in a loop with batch processing
class CreateBulkNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $userIds,
        public string $title,
        public string $message,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public $notification = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            $service = $notificationService->createBulk(
                userIds: $this->userIds,
                title: $this->title,
                message: $this->message,
                referenceType: $this->referenceType,
                referenceId: $this->referenceId,
            );

            if ($this->notification) {
                $service->notifyUsers($this->notification);
            }

        } catch (\Exception $e) {
            Log::error('SendNotificationJob failed', [
                'user_ids' => $this->userIds,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Retry
        }
    }
}
