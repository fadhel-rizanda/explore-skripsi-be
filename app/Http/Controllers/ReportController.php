<?php

namespace App\Http\Controllers;

use App\Enums\ReportReferenceEnum;
use App\Http\Requests\CreateReportRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Resources\ReportResource;
use App\Http\Services\NotificationService;
use App\Models\Report;
use App\Models\Status;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

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
