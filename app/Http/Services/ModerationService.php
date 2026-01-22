<?php

namespace App\Http\Services;

use App\Enums\ReportActionEnum;
use App\Enums\ReportReferenceEnum;
use App\Models\Report;
use App\Models\Status;
use App\Notifications\ReportActionNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModerationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function execute(
        Model $entity,
        bool $isActive,
        ReportReferenceEnum $referenceType,
        ReportActionEnum $action,
        array $recipients,
        ?string $reportId = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use (
            $entity,
            $isActive,
            $referenceType,
            $action,
            $recipients,
            $reportId,
            $notes
        ) {
            $reportStatus = Status::report('resolved');

            $report = null;

            if ($reportId) {
                $report = Report::find($reportId);
                if ($report) {
                    $report->update(['status_id' => $reportStatus->id]);
                }
            }

            if (! $report) {
                $report = Report::create([
                    'reference_type' => $referenceType->value,
                    'reference_id' => $entity->id,
                    'notes' => $notes,
                    'status_id' => $reportStatus->id,
                    'created_by' => auth('api')->id(),
                ]);
            }

            $entity->update(['is_active' => $isActive]);

            $this->notify(
                entity: $entity,
                isActive: $isActive,
                referenceType: $referenceType,
                action: $action,
                recipients: $recipients,
                report: $report
            );
        });
    }

    private function notify(
        Model $entity,
        bool $isActive,
        ReportReferenceEnum $referenceType,
        ReportActionEnum $action,
        array $recipients,
        Report $report
    ): void {
        $title = $isActive
            ? ucfirst($referenceType->value) . ' Restored'
            : ucfirst($referenceType->value) . ' Taken Down';

        $message = $isActive
            ? "Your {$referenceType->value} has been restored after review: "
            : "Your {$referenceType->value} has been taken down due to a report: ";

        $message .= Str::limit($report->notes, 100);

        foreach ($recipients as $user) {
            $this->notificationService->create([
                'title' => $title,
                'message' => $message,
                'user_id' => $user->id,
                'reference_type' => $referenceType->value,
                'reference_id' => $entity->id,
            ]);

            $user->notify(
                new ReportActionNotification(
                    action: $action->value,
                    entityName: $entity->name ?? $entity->title ?? $entity->email ?? 'your ' . strtolower($referenceType->value),
                    entityType: $referenceType->value,
                    notes: $message
                )
            );
        }
    }
}
