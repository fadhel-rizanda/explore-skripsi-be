<?php

namespace App\Http\Controllers;

use App\Enums\AdoptionStageEnum;
use App\Enums\AdoptionStatusEnum;
use App\Enums\AttachmentTypeEnum;
use App\Enums\ModelReferenceEnum;
use App\Enums\StatusTypeEnum;
use App\Enums\TagTypeEnum;
use App\Events\AdoptionUpdated;
use App\Http\Requests\CreateRequirementRequest;
use App\Http\Requests\FillRequirementRequest;
use App\Http\Requests\RejectRequirementRequest;
use App\Http\Services\NotificationService;
use App\Models\Adoption;
use App\Models\AllTag;
use App\Models\Attachment;
use App\Models\Requirement;
use App\Models\Status;
use App\Notifications\AdoptionMailNotification;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

class RequirementController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function listRequirements(Adoption $adoption)
    {
        $requirements = $adoption->requirements()
            ->with([
                'attachment',
                'status',
                'createdBy:id,name,email',
                'updatedBy:id,name,email',
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->sendSuccess(
            'Requirements fetched successfully',
            $requirements
        );
    }

    public function setRequirements(Adoption $adoption, CreateRequirementRequest $request)
    {
        $user = auth('api')->user();

        try {
            DB::beginTransaction();

            $data = collect($request->input('requirements'))
                ->map(fn ($item) => [
                    'id' => \Str::uuid(),
                    'name' => $item['name'],
                    'notes' => $item['notes'] ?? null,
                    'adoption_id' => $adoption->id,
                    'status_id' => Status::getCache(
                        StatusTypeEnum::ADOPTION->value,
                        AdoptionStatusEnum::PENDING->value
                    )->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->toArray();

            Requirement::insert($data);

            DB::commit();

            $usersToNotify = [$adoption->adopter->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'New Adoption Requirements Set',
                message: 'New requirements have been set for the adoption of ' . $adoption->pet->name . '. Please review and complete them.',
                referenceType: ModelReferenceEnum::REQUIREMENT->value,
                referenceId: $adoption->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: AdoptionStageEnum::REQUIREMENT->value,
                    adoption: $adoption,
                    notes: 'New requirements have been set for your adoption application.'
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            $data = [
                'requirements' => $data,
            ];

            return $this->sendSuccess('Requirements set successfully', $data);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error setting requirements', ['error' => $e->getMessage()]);

            return $this->sendError('Error setting requirements.');
        }
    }

    public function fillRequirement(Adoption $adoption, Requirement $requirement, FillRequirementRequest $request)
    {
        $user = auth('api')->user();

        try {
            DB::beginTransaction();

            $requirement->update([
                'attachment_id' => $request->input('attachment_id'),
                'status_id' => Status::getCache(
                    StatusTypeEnum::ADOPTION->value,
                    AdoptionStatusEnum::IN_PROGRESS->value
                )->id,
                'updated_by' => $user->id,
            ]);

            $requirement->setAttachmentMetadata(
                attachmentId: $request->input('attachment_id'),
                modelReference: ModelReferenceEnum::REQUIREMENT->value,
            );

            DB::commit();

            $usersToNotify = [$adoption->provider->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'Adoption Requirement Filled',
                message: 'A requirement has been filled for the adoption of ' . $adoption->pet->name . '. Please review and approve it.',
                referenceType: ModelReferenceEnum::REQUIREMENT->value,
                referenceId: $adoption->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: AdoptionStageEnum::REQUIREMENT->value,
                    adoption: $adoption,
                    notes: 'A requirement has been filled and is pending your approval.'
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            $data = [
                'id' => $requirement->id,
                'name' => $requirement->name,
                'notes' => $requirement->notes,
                'attachment' => $requirement->attachment,
                'status' => $requirement->status,
                'created_by' => [
                    'id' => $requirement->createdBy->id,
                    'name' => $requirement->createdBy->name,
                    'email' => $requirement->createdBy->email,
                ],
                'updated_by' => [
                    'id' => $requirement->updatedBy->id,
                    'name' => $requirement->updatedBy->name,
                    'email' => $requirement->updatedBy->email,
                ],
                'created_at' => $requirement->created_at,
                'updated_at' => $requirement->updated_at,
            ];

            return $this->sendSuccess('Requirement filled successfully', $data);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error filling requirement', ['error' => $e->getMessage()]);

            return $this->sendError('Error filling requirement.');
        }
    }

    public function deleteRequirement(Adoption $adoption, Requirement $requirement)
    {
        if ($requirement->adoption_id !== $adoption->id) {
            return $this->sendError('Requirement does not belong to this adoption.', 403);
        }

        if ($requirement->attachment()->exists()) {
            return $this->sendError('Cannot delete a requirement that has an attachment.', 403);
        }

        try {
            if ($requirement->attachment_id) {
                Attachment::whereId($requirement->attachment_id)->update([
                    'reference_id' => null,
                    'reference_by' => null,
                    'status' => AttachmentTypeEnum::PENDING->value,
                ]);
            }

            $requirement->delete();

            return $this->sendSuccess('Requirement deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Error deleting requirement', ['error' => $e->getMessage()]);

            return $this->sendError('Error deleting requirement.');
        }
    }

    public function approveRequirement(Adoption $adoption, Requirement $requirement)
    {
        if ($requirement->adoption_id !== $adoption->id) {
            return $this->sendError('Requirement does not belong to this adoption.', 403);
        }

        $user = auth('api')->user();

        try {
            DB::beginTransaction();

            $requirement->update([
                'status_id' => Status::getCache(
                    StatusTypeEnum::ADOPTION->value,
                    AdoptionStatusEnum::COMPLETED->value
                )->id,
                'updated_by' => $user->id,
                'is_active' => false,
            ]);

            DB::commit();

            $usersToNotify = [$adoption->adopter->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'Adoption Requirement Approved',
                message: 'A requirement has been approved for the adoption of ' . $adoption->pet->name . '.',
                referenceType: ModelReferenceEnum::REQUIREMENT->value,
                referenceId: $adoption->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: AdoptionStageEnum::REQUIREMENT->value,
                    adoption: $adoption,
                    notes: 'A requirement has been approved.'
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            $data = [
                'id' => $requirement->id,
                'name' => $requirement->name,
                'notes' => $requirement->notes,
                'attachment' => $requirement->attachment,
                'status' => $requirement->status,
                'created_by' => $requirement->createdBy,
                'updated_by' => $requirement->updatedBy,
                'created_at' => $requirement->created_at,
                'updated_at' => $requirement->updated_at,
            ];

            return $this->sendSuccess('Requirement approved successfully', $data);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error approving requirement', ['error' => $e->getMessage()]);

            return $this->sendError('Error approving requirement.');
        }
    }

    public function rejectRequirement(Adoption $adoption, Requirement $requirement, RejectRequirementRequest $request)
    {
        if ($requirement->adoption_id !== $adoption->id) {
            return $this->sendError('Requirement does not belong to this adoption.', 403);
        }

        $user = auth('api')->user();

        try {
            DB::beginTransaction();

            $requirement->update([
                'status_id' => Status::getCache(
                    StatusTypeEnum::ADOPTION->value,
                    AdoptionStatusEnum::REJECTED->value
                )->id,
                'notes' => $request->input('notes'),
                'updated_by' => $user->id,
            ]);

            DB::commit();

            $usersToNotify = [$adoption->adopter->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'Adoption Requirement Rejected',
                message: 'A requirement has been rejected for the adoption of ' . $adoption->pet->name . '. Please review and resubmit it.',
                referenceType: ModelReferenceEnum::REQUIREMENT->value,
                referenceId: $adoption->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: AdoptionStageEnum::REQUIREMENT->value,
                    adoption: $adoption,
                    notes: 'A requirement has been rejected. Please review and resubmit.'
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            $data = [
                'id' => $requirement->id,
                'name' => $requirement->name,
                'notes' => $requirement->notes,
                'attachment' => $requirement->attachment,
                'status' => $requirement->status,
                'created_by' => $requirement->createdBy,
                'updated_by' => $requirement->updatedBy,
                'created_at' => $requirement->created_at,
                'updated_at' => $requirement->updated_at,
            ];

            return $this->sendSuccess('Requirement rejected successfully', $data);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error rejecting requirement', ['error' => $e->getMessage()]);

            return $this->sendError('Error rejecting requirement.');
        }
    }

    public function finalizedRequirements(Adoption $adoption)
    {
        $user = auth('api')->user();

        $finalStatusIds = Status::where('type', StatusTypeEnum::ADOPTION->value)
            ->whereIn('name', [
                AdoptionStatusEnum::COMPLETED->value,
                AdoptionStatusEnum::REJECTED->value,
            ])
            ->pluck('id');

        $hasPending = $adoption->requirements()
            ->whereNotIn('status_id', $finalStatusIds)
            ->exists();

        if ($hasPending) {
            return $this->sendError(
                'All requirements must be completed or rejected before finalizing adoption.',
                422
            );
        }

        try {
            DB::beginTransaction();
            $adoption->update([
                'status_id' => Status::getCache(
                    StatusTypeEnum::ADOPTION->value,
                    AdoptionStatusEnum::NEED_AN_ACTION->value
                )->id,
                'stage_tag_id' => AllTag::getCache(
                    TagTypeEnum::ADOPTION_STAGE->value,
                    AdoptionStageEnum::HANDOVER->value
                )->id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            $requirements = $adoption->requirements()
                ->whereIn('status_id', $finalStatusIds)
                ->with([
                    'attachment',
                    'status',
                    'createdBy:id,name,email',
                    'updatedBy:id,name,email',
                ])
                ->orderBy('created_at')
                ->get();

            $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'Adoption Finalized',
                message: 'The adoption process for ' . $adoption->pet->name . ' has been finalized.',
                referenceType: ModelReferenceEnum::REQUIREMENT->value,
                referenceId: $adoption->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: AdoptionStageEnum::REQUIREMENT->value,
                    adoption: $adoption,
                    notes: 'The adoption process has been finalized.'
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            $data = [
                'adoption_status' => $adoption->status,
                'adoption_stage' => $adoption->stageTag,
            ];

            return $this->sendSuccess('Finalized requirements fetched successfully', $data);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error finalizing requirements', ['error' => $e->getMessage()]);

            return $this->sendError('Failed to finalize adoption.');
        }
    }
}
