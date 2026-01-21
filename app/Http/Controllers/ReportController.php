<?php

namespace App\Http\Controllers;

use App\Enums\ReportActionEnum;
use App\Enums\ReportReferenceEnum;
use App\Http\Requests\CreateReportRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Resources\ReportResource;
use App\Http\Services\NotificationService;
use App\Models\Community;
use App\Models\Pet;
use App\Models\Post;
use App\Models\Report;
use App\Models\Status;
use App\Models\User;
use App\Notifications\ReportActionNotification;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    use ResponseAPI;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function listReports(GetAllRequest $request)
    {
        try {
            $reports = $this->getReportsQuery($request);

            return $this->sendSuccessPagination(
                'Report retrieved successfully.',
                $reports
            );
        } catch (\Throwable $e) {
            \Log::error('Error fetching reports', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching reports.');
        }
    }

    public function reportDetail(Report $report)
    {
        try {
            $report->load([
                'createdBy:id,name,email,avatar,is_active',
                'createdBy.attachment:id,public_url',
                'tags:id,name,type',
                'status:id,name,type,color_code',
            ]);

            return $this->sendSuccess('Report details retrieved successfully.', new ReportResource($report));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Report not found.', 404);
        } catch (\Throwable $e) {
            \Log::error('Error fetching report details', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching report details.');
        }
    }

    public function createReport(CreateReportRequest $request)
    {
        try {
            DB::beginTransaction();
            $status = Status::report('active');

            $report = Report::create([
                'reference_type' => $request->input('reference_type'),
                'reference_id' => $request->input('reference_id'),
                'notes' => $request->input('notes'),
                'status_id' => $status->id,
                'created_by' => auth('api')->id(),
            ]);
            if ($request->has('tag_ids')) {
                $report->tags()->sync($request->input('tag_ids'));
            }
            DB::commit();

            return $this->sendSuccess('Report created successfully.', new ReportResource($report));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error creating report', ['error' => $e->getMessage()]);

            return $this->sendError('Error creating report.');
        }
    }

    public function deleteReport(Report $report)
    {
        try {
            $report->delete();

            return $this->sendSuccess('Report deleted successfully.');
        } catch (\Throwable $e) {
            \Log::error('Error deleting report', ['error' => $e->getMessage()]);

            return $this->sendError('Error deleting report.');
        }
    }

    public function updateReportStatus(Report $report, Status $status)
    {
        try {
            $report->update(['status_id' => $status->id]);

            return $this->sendSuccess('Report status updated successfully.');
        } catch (\Throwable $e) {
            \Log::error('Error updating report status', ['error' => $e->getMessage()]);

            return $this->sendError('Error updating report status.');
        }
    }

    private function processModerationAction($entity, Report $report, $isActive, $action, $referenceType, $entityName, $recipients)
    {
        DB::beginTransaction();

        $entity->update(['is_active' => $isActive]);
        $report->update(['status_id' => Status::report('resolved')->id]);

        DB::commit();

        $title = $isActive ? ucfirst($referenceType) . ' Restored' : ucfirst($referenceType) . ' Taken Down';
        $message = $isActive
            ? "Your {$referenceType} has been restored after review of the report: " . Str::limit($report->notes, 100)
            : "Your {$referenceType} has been taken down due to a report: " . Str::limit($report->notes, 100);

        foreach ($recipients as $user) {
            $this->notificationService->create([
                'title' => $title,
                'message' => $message,
                'user_id' => $user->id,
                'reference_type' => $referenceType,
                'reference_id' => $entity->id,
            ]);

            $user->notify(new ReportActionNotification(
                action: $action,
                entityName: $entityName,
                entityType: $referenceType,
                notes: $message
            ));
        }
    }

    public function deactivateUser(Report $report, User $user)
    {
        try {
            $this->processModerationAction(
                $user,
                $report,
                false,
                ReportActionEnum::DEACTIVATED->value,
                ReportReferenceEnum::USER->value,
                $user->name ?? $user->email,
                [$user]
            );

            return $this->sendSuccess('User deactivated and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error deactivating user', ['error' => $e->getMessage()]);
            return $this->sendError('Error deactivating user and resolving report.');
        }
    }

    public function activateUser(Report $report, User $user)
    {
        try {
            $this->processModerationAction(
                $user,
                $report,
                true,
                ReportActionEnum::ACTIVATED->value,
                ReportReferenceEnum::USER->value,
                $user->name ?? $user->email,
                [$user]
            );

            return $this->sendSuccess('User activated and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error activating user', ['error' => $e->getMessage()]);
            return $this->sendError('Error activating user and resolving report.');
        }
    }

    public function takeDownPost(Report $report, Post $post)
    {
        try {
            $user = $post->createdBy;
            if (!$user) {
                return $this->sendError('Post owner not found.');
            }

            $this->processModerationAction(
                $post,
                $report,
                false,
                ReportActionEnum::TAKEDOWN->value,
                ReportReferenceEnum::POST->value,
                $post->title ?? 'your post',
                [$user]
            );

            return $this->sendSuccess('Post taken down and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error taking down post', ['error' => $e->getMessage()]);
            return $this->sendError('Error taking down post and resolving report.');
        }
    }

    public function restorePost(Report $report, Post $post)
    {
        try {
            $user = $post->createdBy;
            if (!$user) {
                return $this->sendError('Post owner not found.');
            }

            $this->processModerationAction(
                $post,
                $report,
                true,
                ReportActionEnum::RESTORED->value,
                ReportReferenceEnum::POST->value,
                $post->title ?? 'your post',
                [$user]
            );

            return $this->sendSuccess('Post restored and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error restoring post', ['error' => $e->getMessage()]);
            return $this->sendError('Error restoring post and resolving report.');
        }
    }

    public function takeDownCommunity(Report $report, Community $community)
    {
        try {
            $recipients = collect()
                ->merge($community->admins()->get())
                ->push($community->createdBy()->first())
                ->filter()
                ->unique('id');

            $this->processModerationAction(
                $community,
                $report,
                false,
                ReportActionEnum::TAKEDOWN->value,
                ReportReferenceEnum::COMMUNITY->value,
                $community->name,
                $recipients
            );

            return $this->sendSuccess('Community taken down and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error taking down community', ['error' => $e->getMessage()]);
            return $this->sendError('Error taking down community and resolving report.');
        }
    }

    public function restoreCommunity(Report $report, Community $community)
    {
        try {
            $recipients = collect()
                ->merge($community->admins()->get())
                ->push($community->createdBy()->first())
                ->filter()
                ->unique('id');

            $this->processModerationAction(
                $community,
                $report,
                true,
                ReportActionEnum::RESTORED->value,
                ReportReferenceEnum::COMMUNITY->value,
                $community->name,
                $recipients
            );

            return $this->sendSuccess('Community restored and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error restoring community', ['error' => $e->getMessage()]);
            return $this->sendError('Error restoring community and resolving report.');
        }
    }

    public function takeDownPet(Report $report, Pet $pet)
    {
        try {
            $user = $pet->user;
            if (!$user) {
                return $this->sendError('Pet owner not found.');
            }

            $this->processModerationAction(
                $pet,
                $report,
                false,
                ReportActionEnum::TAKEDOWN->value,
                ReportReferenceEnum::PET->value,
                $pet->name ?? 'your pet',
                [$user]
            );

            return $this->sendSuccess('Pet taken down and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error taking down pet', ['error' => $e->getMessage()]);
            return $this->sendError('Error taking down pet and resolving report.');
        }
    }

    public function restorePet(Report $report, Pet $pet)
    {
        try {
            $user = $pet->user;
            if (!$user) {
                return $this->sendError('Pet owner not found.');
            }

            $this->processModerationAction(
                $pet,
                $report,
                true,
                ReportActionEnum::RESTORED->value,
                ReportReferenceEnum::PET->value,
                $pet->name ?? 'your pet',
                [$user]
            );

            return $this->sendSuccess('Pet restored and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error restoring pet', ['error' => $e->getMessage()]);
            return $this->sendError('Error restoring pet and resolving report.');
        }
    }

    private function getReportsQuery(GetAllRequest $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'created_at');
        $referenceType = $request->query('reference_type');
        $tagId = $request->query('tag_id');

        $allowedSorts = ['id', 'reference_type', 'reference_id', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        if ($referenceType && ! in_array($referenceType, ReportReferenceEnum::allValues())) {
            $referenceType = null;
        }

        $reports = Report::with([
            'createdBy:id,name,email',
            'tags:id,name,type',
            'status:id,name,type,color_code',
        ])
            ->when($search, function ($q) use ($search) {
                $q->where('id', 'ILIKE', "%{$search}%");
            })
            ->when($referenceType, fn ($q) => $q->where('reference_type', $referenceType))
            ->when($tagId, function ($q) use ($tagId) {
                $q->whereHas('tags', fn ($query) => $query->where($query->getModel()->getTable() . '.id', $tagId));
            })
            ->orderBy($sortBy, 'desc')->paginate($perPage);

        return ReportResource::collection($reports);
    }
}
