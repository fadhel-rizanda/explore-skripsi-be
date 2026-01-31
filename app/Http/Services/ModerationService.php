<?php

namespace App\Http\Services;

use App\Enums\ActionEnum;
use App\Enums\ModelReferenceEnum;
use App\Enums\ReportStatusEnum;
use App\Enums\StatusTypeEnum;
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
        ModelReferenceEnum $referenceType,
        ActionEnum $action,
        array $recipientIds,
        string $moderatorId,
        ?string $reportId = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use (
            $entity,
            $isActive,
            $referenceType,
            $action,
            $recipientIds,
            $reportId,
            $notes,
            $moderatorId
        ) {
            $reportStatus = Status::getCache(StatusTypeEnum::REPORT->value, ReportStatusEnum::RESOLVED->value);

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
                    'created_by' => $moderatorId,
                ]);
            }

            $entity->update(['is_active' => $isActive]);

            $this->notify(
                entity: $entity,
                isActive: $isActive,
                referenceType: $referenceType,
                action: $action,
                recipientIds: $recipientIds,
                report: $report
            );
        });
    }

    private function notify(
        Model $entity,
        bool $isActive,
        ModelReferenceEnum $referenceType,
        ActionEnum $action,
        array $recipientIds,
        Report $report
    ): void {
        $title = $isActive
            ? ucfirst($referenceType->value) . ' Restored'
            : ucfirst($referenceType->value) . ' Taken Down';

        $message = $isActive
            ? "Your {$referenceType->value} has been restored after review: "
            : "Your {$referenceType->value} has been taken down due to a report: ";

        $message .= Str::limit($report->notes, 100);

        $this->notificationService->createBulk(
            userIds: $recipientIds,
            title: $title,
            message: $message,
            referenceType: $referenceType->value,
            referenceId: $entity->id,
        )->broadcast()->notifyUsers(
            new ReportActionNotification(
                action: $action->value,
                entityName: $entity->name ?? $entity->title ?? $entity->email ?? 'your ' . strtolower($referenceType->value),
                entityType: $referenceType->value,
                notes: $message
            )
        );
    }
}
