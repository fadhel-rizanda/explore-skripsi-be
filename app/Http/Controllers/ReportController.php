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
            $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
            $reports = $this->getReportsQuery($request, $isAdmin);

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

    public function deactivateUser(Report $report, User $user)
    {
        try {
            DB::beginTransaction();

            $statusResolved = Status::report('resolved');

            $user->update(['is_active' => false]);
            $report->update(['status_id' => $statusResolved->id]);

            DB::commit();

            $this->notificationService->create([
                'title' => 'Account Deactivated',
                'message' => 'Your account has been deactivated due to a report: ' . Str::limit($report->notes, 100),
                'user_id' => $user->id,
                'reference_type' => ReportReferenceEnum::USER->value,
                'reference_id' => $user->id,
            ]);

            $user->notify(new ReportActionNotification(
                action: ReportActionEnum::DEACTIVATED->value,
                entityName: $user->name ?? $user->email,
                entityType: ReportReferenceEnum::USER->value,
                notes: 'Your account has been deactivated due to a report: ' . Str::limit($report->notes, 100)
            ));

            return $this->sendSuccess('User deactivated and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error deactivating user and resolving report', ['error' => $e->getMessage()]);

            return $this->sendError('Error deactivating user and resolving report.');
        }
    }

    public function activateUser(Report $report, User $user)
    {
        try {
            DB::beginTransaction();

            $user->update(['is_active' => true]);
            $statusResolved = Status::report('resolved');
            $report->update(['status_id' => $statusResolved->id]);

            DB::commit();

            $this->notificationService->create([
                'title' => 'Account Reactivated',
                'message' => 'Your account has been reactivated after review of the report: ' . Str::limit($report->notes, 100),
                'user_id' => $user->id,
                'reference_type' => ReportReferenceEnum::USER->value,
                'reference_id' => $user->id,
            ]);

            $user->notify(new ReportActionNotification(
                action: ReportActionEnum::ACTIVATED->value,
                entityName: $user->name ?? $user->email,
                entityType: ReportReferenceEnum::USER->value,
                notes: 'Your account has been reactivated after review of the report: ' . Str::limit($report->notes, 100)
            ));

            return $this->sendSuccess('User activated and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error activating user and resolving report', ['error' => $e->getMessage()]);

            return $this->sendError('Error activating user and resolving report.');
        }
    }

    public function takeDownPost(Report $report, Post $post)
    {
        try {
            DB::beginTransaction();

            $post->update(['is_active' => false]);
            $statusResolved = Status::report('resolved');
            $report->update(['status_id' => $statusResolved->id]);

            DB::commit();

            $user = $post->createdBy;

            if ($user) {
                $this->notificationService->create([
                    'title' => 'Post Taken Down',
                    'message' => 'Your post has been taken down due to a report: ' . Str::limit($report->notes, 100),
                    'user_id' => $user->id,
                    'reference_type' => ReportReferenceEnum::POST->value,
                    'reference_id' => $post->id,
                ]);

                $user->notify(new ReportActionNotification(
                    action: ReportActionEnum::TAKEDOWN->value,
                    entityName: $post->title ?? 'your post',
                    entityType: ReportReferenceEnum::POST->value,
                    notes: 'Your post has been taken down due to a report: ' . Str::limit($report->notes, 100)
                ));
            }

            return $this->sendSuccess('Post taken down and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error taking down post and resolving report', ['error' => $e->getMessage()]);

            return $this->sendError('Error taking down post and resolving report.');
        }
    }

    public function restorePost(Report $report, Post $post)
    {
        try {
            DB::beginTransaction();

            $post->update(['is_active' => true]);
            $statusResolved = Status::report('resolved');
            $report->update(['status_id' => $statusResolved->id]);

            DB::commit();

            $user = $post->createdBy;

            if ($user) {
                $this->notificationService->create([
                    'title' => 'Post Restored',
                    'message' => 'Your post has been restored after review of the report: ' . Str::limit($report->notes, 100),
                    'user_id' => $user->id,
                    'reference_type' => ReportReferenceEnum::POST->value,
                    'reference_id' => $post->id,
                ]);

                $user->notify(new ReportActionNotification(
                    action: ReportActionEnum::RESTORED->value,
                    entityName: $post->title ?? 'your post',
                    entityType: ReportReferenceEnum::POST->value,
                    notes: 'Your post has been restored after review of the report: ' . Str::limit($report->notes, 100)
                ));
            }

            return $this->sendSuccess('Post restored and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error restoring post and resolving report', ['error' => $e->getMessage()]);

            return $this->sendError('Error restoring post and resolving report.');
        }
    }

    public function takeDownCommunity(Report $report, Community $community)
    {
        try {
            DB::beginTransaction();

            $community->update(['is_active' => false]);
            $statusResolved = Status::report('resolved');
            $report->update(['status_id' => $statusResolved->id]);

            DB::commit();

            $recipients = collect()
                ->merge($community->admins()->get())
                ->push($community->createdBy()->first())
                ->filter()
                ->unique('id');
            $recipients->each(function ($user) use ($community, $report) {
                $this->notificationService->create([
                    'title' => 'Community Taken Down',
                    'message' => 'Your community has been taken down due to a report: ' . Str::limit($report->notes, 100),
                    'user_id' => $user->id,
                    'reference_type' => ReportReferenceEnum::COMMUNITY->value,
                    'reference_id' => $community->id,
                ]);

                $user->notify(new ReportActionNotification(
                    action: ReportActionEnum::TAKEDOWN->value,
                    entityName: $community->name,
                    entityType: ReportReferenceEnum::COMMUNITY->value,
                    notes: 'Your community has been taken down due to a report: ' . Str::limit($report->notes, 100)
                ));
            });

            return $this->sendSuccess('Community taken down and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error taking down community and resolving report', ['error' => $e->getMessage()]);

            return $this->sendError('Error taking down community and resolving report.');
        }
    }

    public function restoreCommunity(Report $report, Community $community)
    {
        try {
            DB::beginTransaction();

            $community->update(['is_active' => true]);
            $statusResolved = Status::report('resolved');
            $report->update(['status_id' => $statusResolved->id]);

            DB::commit();

            $recipients = collect()
                ->merge($community->admins()->get())
                ->push($community->createdBy()->first())
                ->filter()
                ->unique('id');
            $recipients->each(function ($user) use ($community, $report) {
                $this->notificationService->create([
                    'title' => 'Community Restored',
                    'message' => 'Your community has been restored after review of the report: ' . Str::limit($report->notes, 100),
                    'user_id' => $user->id,
                    'reference_type' => ReportReferenceEnum::COMMUNITY->value,
                    'reference_id' => $community->id,
                ]);

                $user->notify(new ReportActionNotification(
                    action: ReportActionEnum::RESTORED->value,
                    entityName: $community->name,
                    entityType: ReportReferenceEnum::COMMUNITY->value,
                    notes: 'Your community has been restored after review of the report: ' . Str::limit($report->notes, 100)
                ));
            });

            return $this->sendSuccess('Community restored and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error restoring community and resolving report', ['error' => $e->getMessage()]);

            return $this->sendError('Error restoring community and resolving report.');
        }
    }

    public function takeDownPet(Report $report, Pet $pet)
    {
        try {
            DB::beginTransaction();

            $pet->update(['is_active' => false]);
            $statusResolved = Status::report('resolved');
            $report->update(['status_id' => $statusResolved->id]);

            $user = $pet->user;
            if ($user) {
                $this->notificationService->create([
                    'title' => 'Pet Listing Taken Down',
                    'message' => 'Your pet listing has been taken down due to a report: ' . Str::limit($report->notes, 100),
                    'user_id' => $user->id,
                    'reference_type' => ReportReferenceEnum::PET->value,
                    'reference_id' => $pet->id,
                ]);

                $user->notify(new ReportActionNotification(
                    action: ReportActionEnum::TAKEDOWN->value,
                    entityName: $pet->name ?? 'your pet',
                    entityType: ReportReferenceEnum::PET->value,
                    notes: 'Your pet listing has been taken down due to a report: ' . Str::limit($report->notes, 100)
                ));
            }

            DB::commit();

            return $this->sendSuccess('Pet taken down and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error taking down pet and resolving report', ['error' => $e->getMessage()]);

            return $this->sendError('Error taking down pet and resolving report.');
        }
    }

    public function restorePet(Report $report, Pet $pet)
    {
        try {
            DB::beginTransaction();

            $pet->update(['is_active' => true]);
            $statusResolved = Status::report('resolved');
            $report->update(['status_id' => $statusResolved->id]);

            DB::commit();

            $user = $pet->user;
            if ($user) {
                $this->notificationService->create([
                    'title' => 'Pet Listing Restored',
                    'message' => 'Your pet listing has been restored after review of the report: ' . Str::limit($report->notes, 100),
                    'user_id' => $user?->id,
                    'reference_type' => ReportReferenceEnum::PET->value,
                    'reference_id' => $pet->id,
                ]);

                $user->notify(new ReportActionNotification(
                    action: ReportActionEnum::RESTORED->value,
                    entityName: $pet->name ?? 'your pet',
                    entityType: ReportReferenceEnum::PET->value,
                    notes: 'Your pet listing has been restored after review of the report: ' . Str::limit($report->notes, 100)
                ));
            }

            return $this->sendSuccess('Pet restored and report resolved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error restoring pet and resolving report', ['error' => $e->getMessage()]);

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
