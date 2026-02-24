<?php

namespace App\Http\Controllers;

use App\Enums\AdoptionStageEnum;
use App\Enums\AdoptionStatusEnum;
use App\Enums\ModelReferenceEnum;
use App\Enums\PetStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Events\AdoptionUpdated;
use App\Http\Requests\CreateScheduleRequest;
use App\Http\Requests\SetHandOverEvidenceRequest;
use App\Http\Services\MeetNGreetService;
use App\Http\Services\NotificationService;
use App\Models\Adoption;
use App\Models\Handover;
use App\Models\Attachment;
use App\Models\AllTag;
use App\Enums\AttachmentTypeEnum;
use App\Models\Status;
use App\Notifications\AdoptionMailNotification;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

class HandoverController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private NotificationService $notificationService,
        private MeetNGreetService   $meetNGreetService
    )
    {
    }

    public function handover(Adoption $adoption)
    {
        $handover = $adoption->handovers()
            ->with(['meetNGreet.schedule', 'meetNGreet.schedule.address', 'status', 'attachments'])
            ->orderBy('updated_at', 'desc')
            ->first();

        return $this->sendSuccess('Handover fetched successfully', $handover);
    }

    public function createMeetNGreetSchedule(Adoption $adoption, CreateScheduleRequest $request)
    {
        $user = auth('api')->user();

        try {
            DB::beginTransaction();
            $meetNGreet = $this->meetNGreetService->createSchedule(
                adoption: $adoption,
                data: $request->validated(),
                stage: "handover"
            );

            $handover = Handover::create(
                [
                    'adoption_id' => $adoption->id,
                    'status_id' => Status::getCache(
                        StatusTypeEnum::ADOPTION->value,
                        AdoptionStatusEnum::IN_PROGRESS->value
                    )->id,
                    'meet_n_greet_id' => $meetNGreet->id,
                    'created_by' => $user->id,
                ]
            );

            DB::commit();

            $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];

            $notification = $this->notificationService
                ->createBulk(
                    userIds: $usersToNotify,
                    title: 'Handover Scheduled',
                    message: 'Handover has been scheduled for ' . ($adoption->pet->name ?? 'Unnamed Pet'),
                    referenceType: ModelReferenceEnum::HANDOVER->value,
                    referenceId: $handover->id,
                )
                ->notifyUsers(
                    new AdoptionMailNotification(
                        action: AdoptionStageEnum::HANDOVER->value,
                        adoption: $adoption
                    )
                )
                ->getNotifications()
                ->first();
            broadcast(new AdoptionUpdated($notification));

            $data = [
                'id' => $handover->id,
                'adoption_id' => $handover->adoption_id,
                'status' => $handover->status,
                'created_at' => $handover->created_at,
                'updated_at' => $handover->updated_at,
                'meet_n_greet' => [
                    'id' => $meetNGreet->id,
                    'adoption_id' => $meetNGreet->adoption_id,
                    'adopter_confirmed' => $meetNGreet->adopter_confirmed,
                    'adopter_confirmed_at' => $meetNGreet->adopter_confirmed_at,
                    'provider_confirmed' => $meetNGreet->provider_confirmed,
                    'provider_confirmed_at' => $meetNGreet->provider_confirmed_at,
                    'status' => $meetNGreet->status,
                    'schedule' => [
                        'id' => $meetNGreet->schedule->id,
                        'scheduled_time' => $meetNGreet->schedule->scheduled_time,
                        'notes' => $meetNGreet->schedule->notes,
                        'address' => $meetNGreet->schedule->address,
                        'created_at' => $meetNGreet->schedule->created_at,
                        'updated_at' => $meetNGreet->schedule->updated_at,
                    ],
                    'created_at' => $meetNGreet->created_at,
                    'updated_at' => $meetNGreet->updated_at,
                ],
            ];

            return $this->sendSuccess('Handover Meet and Greet ' . ($request->has('meet_n_greet_id') ? 'updated' : 'scheduled') . ' successfully', $data);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error scheduling handover', ['error' => $e->getMessage()]);

            return $this->sendError('Error scheduling handover.');
        }
    }

    public function updateMeetNGreetSchedule(Adoption $adoption, Handover $handover, CreateScheduleRequest $request)
    {
        $user = auth('api')->user();

        try {
            DB::beginTransaction();
            $meetNGreet = $this->meetNGreetService->updateSchedule(
                adoption: $adoption,
                data: $request->validated(),
                meetNGreet: $handover->meetNGreet,
            );

            $handover->update([
                'status_id' => Status::getCache(
                    StatusTypeEnum::ADOPTION->value,
                    AdoptionStatusEnum::IN_PROGRESS->value
                )->id,
                'meet_n_greet_id' => $meetNGreet->id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            $handover->refresh();

            $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];

            $notification = $this->notificationService
                ->createBulk(
                    userIds: $usersToNotify,
                    title: 'Handover Scheduled',
                    message: 'Handover has been scheduled for ' . ($adoption->pet->name ?? 'Unnamed Pet'),
                    referenceType: ModelReferenceEnum::HANDOVER->value,
                    referenceId: $handover->id,
                )
                ->notifyUsers(
                    new AdoptionMailNotification(
                        action: AdoptionStageEnum::HANDOVER->value,
                        adoption: $adoption
                    )
                )
                ->getNotifications()
                ->first();
            broadcast(new AdoptionUpdated($notification));

            $data = [
                'id' => $handover->id,
                'adoption_id' => $handover->adoption_id,
                'status' => $handover->status,
                'created_at' => $handover->created_at,
                'updated_at' => $handover->updated_at,
                'meet_n_greet' => [
                    'id' => $meetNGreet->id,
                    'adoption_id' => $meetNGreet->adoption_id,
                    'adopter_confirmed' => $meetNGreet->adopter_confirmed,
                    'adopter_confirmed_at' => $meetNGreet->adopter_confirmed_at,
                    'provider_confirmed' => $meetNGreet->provider_confirmed,
                    'provider_confirmed_at' => $meetNGreet->provider_confirmed_at,
                    'status' => $meetNGreet->status,
                    'schedule' => [
                        'id' => $meetNGreet->schedule->id,
                        'scheduled_time' => $meetNGreet->schedule->scheduled_time,
                        'notes' => $meetNGreet->schedule->notes,
                        'address' => $meetNGreet->schedule->address,
                        'created_at' => $meetNGreet->schedule->created_at,
                        'updated_at' => $meetNGreet->schedule->updated_at,
                    ],
                    'created_at' => $meetNGreet->created_at,
                    'updated_at' => $meetNGreet->updated_at,
                ],
            ];

            return $this->sendSuccess('Handover Meet and Greet ' . ($request->has('meet_n_greet_id') ? 'updated' : 'scheduled') . ' successfully', $data);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error scheduling handover', ['error' => $e->getMessage()]);

            return $this->sendError('Error scheduling handover.');
        }
    }

    public function approveMeetNGreet(Adoption $adoption, Handover $handover)
    {
        try {
            if ($handover->adoption_id !== $adoption->id) {
                return $this->sendError('Handover does not belong to this adoption.', 403);
            }

            $meetNGreet = $handover->meetNGreet;
            $meetNGreet = $this->meetNGreetService->approve($adoption, $meetNGreet);

            if ($meetNGreet->adopter_confirmed && $meetNGreet->provider_confirmed) {
                $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];
                $notification = $this->notificationService->createBulk(
                    userIds: $usersToNotify,
                    title: 'Handover Meet and Greet Completed',
                    message: 'The Handover Meet and Greet has been completed for the adoption of ' . ($adoption->pet->name ?? 'Unnamed Pet'),
                    referenceType: ModelReferenceEnum::HANDOVER->value,
                    referenceId: $handover->id,
                )->notifyUsers(new AdoptionMailNotification(
                    action: AdoptionStageEnum::HANDOVER->value,
                    adoption: $adoption,
                    notes: 'The Handover Meet and Greet has been completed.'
                ))->getNotifications()->first();

                broadcast(new AdoptionUpdated($notification));
            }

            $data = [
                'id' => $handover->id,
                'adoption_id' => $handover->adoption_id,
                'status' => $handover->status,
                'created_at' => $handover->created_at,
                'updated_at' => $handover->updated_at,
                'meet_n_greet' => [
                    'id' => $meetNGreet->id,
                    'adoption_id' => $meetNGreet->adoption_id,
                    'adopter_confirmed' => $meetNGreet->adopter_confirmed,
                    'adopter_confirmed_at' => $meetNGreet->adopter_confirmed_at,
                    'provider_confirmed' => $meetNGreet->provider_confirmed,
                    'provider_confirmed_at' => $meetNGreet->provider_confirmed_at,
                    'status' => $meetNGreet->status,
                    'schedule' => [
                        'id' => $meetNGreet->schedule->id,
                        'scheduled_time' => $meetNGreet->schedule->scheduled_time,
                        'notes' => $meetNGreet->schedule->notes,
                        'address' => $meetNGreet->schedule->address,
                        'created_at' => $meetNGreet->schedule->created_at,
                        'updated_at' => $meetNGreet->schedule->updated_at,
                    ],
                    'created_at' => $meetNGreet->created_at,
                    'updated_at' => $meetNGreet->updated_at,
                ],
            ];

            return $this->sendSuccess('Meet and Greet approved successfully', $data);

        } catch (\Throwable $e) {
            \Log::error('Error approving Meet and Greet', ['error' => $e->getMessage()]);

            return $this->sendError('Error approving Meet and Greet.');
        }
    }

    public function setEvidence(Adoption $adoption, Handover $handover, SetHandOverEvidenceRequest $request)
    {
        if ($handover->adoption_id !== $adoption->id) {
            return $this->sendError('Handover does not belong to this adoption.', 403);
        }

        try {
            DB::beginTransaction();

            $role = $adoption->adopter_id === auth()->id() ? 'adopter' : 'provider';
            $otherRole = $role === 'adopter' ? 'provider' : 'adopter';

            $currentIds = $request->input('attachment_ids');

            $oldAllIds = $handover->attachments()->pluck('mt_attachment.id')->toArray();
            $otherRoleIds = $handover->attachments()
                ->wherePivot('uploaded_by_role', $otherRole)
                ->pluck('mt_attachment.id')
                ->toArray();

            $syncData = collect($currentIds)
                ->mapWithKeys(fn($id) => [$id => ['uploaded_by_role' => $role]])
                ->merge(
                    collect($otherRoleIds)
                        ->mapWithKeys(fn($id) => [$id => ['uploaded_by_role' => $otherRole]])
                )
                ->toArray();

            $mergedIds = array_merge($currentIds, $otherRoleIds);

            $handover->attachments()->sync($syncData);

            $removedIds = array_diff($oldAllIds, $mergedIds);
            if (!empty($removedIds)) {
                Attachment::whereIn('id', $removedIds)->update([
                    'reference_id' => null,
                    'reference_by' => null,
                    'status' => AttachmentTypeEnum::PENDING->value,
                ]);
            }

            if (!empty($currentIds)) {
                Attachment::whereIn('id', $currentIds)->update([
                    'reference_id' => $handover->id,
                    'reference_by' => ModelReferenceEnum::HANDOVER->value,
                    'status' => AttachmentTypeEnum::COMPLETED->value,
                ]);
            }

            $adopterAttached = $handover->attachments()
                ->wherePivot('uploaded_by_role', 'adopter')
                ->exists();
            $providerAttached = $handover->attachments()
                ->wherePivot('uploaded_by_role', 'provider')
                ->exists();

            if ($adopterAttached && $providerAttached
                && $handover->status->name === AdoptionStatusEnum::IN_PROGRESS->value
                && $adoption->status->name === AdoptionStatusEnum::IN_PROGRESS->value
            ) {
                $adoption->update([
                    'status_id' => Status::getCache(
                        StatusTypeEnum::ADOPTION->value,
                        AdoptionStatusEnum::NEED_AN_ACTION->value
                    )->id,
                ]);
            }

            DB::commit();

            $handover->load('attachments', 'status');

            return $this->sendSuccess('Handover evidence updated successfully', [
                'id' => $handover->id,
                'adoption_id' => $handover->adoption_id,
                'status' => $handover->status,
                'attachments' => $handover->attachments,
                'created_at' => $handover->created_at,
                'updated_at' => $handover->updated_at,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error updating Handover evidence', ['error' => $e->getMessage()]);
            return $this->sendError('Error updating Handover evidence.');
        }
    }

    public function finalize(Adoption $adoption, Handover $handover)
    {
        $user = auth('api')->user();
        $adopter = $adoption->adopter;
        $provider = $adoption->provider;

        if ($handover->adoption_id !== $adoption->id) {
            return $this->sendError('Handover does not belong to this adoption.', 403);
        }

        $completedStatusId = Status::getCache(
            StatusTypeEnum::ADOPTION->value,
            AdoptionStatusEnum::COMPLETED->value
        )->id;

        try {
            DB::beginTransaction();
            $updateData = [];

            if ($user->id === $adopter->id && !$handover->adopter_finalized) {
                $updateData['adopter_finalized'] = true;
                $updateData['adopter_finalized_at'] = now();
            }

            if ($user->id === $provider->id && !$handover->provider_finalized) {
                $updateData['provider_finalized'] = true;
                $updateData['provider_finalized_at'] = now();
            }

            if (
                $user->hasRole(RoleEnum::ADMIN->value) && !$handover->admin_finalized && $handover->adopter_finalized && $handover->provider_finalized) {
                $updateData['admin_finalized'] = true;
                $updateData['admin_finalized_at'] = now();
            }

            if (empty($updateData)) {
                return $this->sendError('No finalization action allowed.', 403);
            }

            $updateData['updated_by'] = $user->id;
            $handover->update($updateData);
            $handover->refresh();

            if ($handover->adopter_finalized && $handover->provider_finalized && $handover->admin_finalized) {
                $handover->update([
                    'status_id' => $completedStatusId,
                    'is_active' => false,
                    'updated_by' => $user->id,
                ]);

                $adoption->update([
                    'status_id' => $completedStatusId,
                    'stage_tag_id' => AllTag::getCache(
                        StatusTypeEnum::ADOPTION->value,
                        AdoptionStageEnum::COMPLETED->value
                    )->id,
                    'updated_by' => $user->id,
                    'is_active' => false,
                ]);

                $adoption->pet()->update([
                    'status_id' => Status::getCache(
                        StatusTypeEnum::PET->value,
                        PetStatusEnum::ADOPTED->value
                    )->id,
                ]);
            }

            DB::commit();

            $notification = $this->notificationService->createBulk(
                userIds: [$adoption->adopter->id, $adoption->provider->id],
                title: 'Handover Finalized',
                message: 'The handover has been finalized for the adoption of ' . ($adoption->pet->name ?? 'Unnamed Pet'),
                referenceType: ModelReferenceEnum::HANDOVER->value,
                referenceId: $handover->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: AdoptionStageEnum::HANDOVER->value,
                    adoption: $adoption,
                    notes: 'The handover has been finalized.'
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            $data = [
                'id' => $handover->id,
                'adoption_id' => $handover->adoption_id,
                'adopter_finalized' => $handover->adopter_finalized,
                'adopter_finalized_at' => $handover->adopter_finalized_at,
                'provider_finalized' => $handover->provider_finalized,
                'provider_finalized_at' => $handover->provider_finalized_at,
                'admin_finalized' => $handover->admin_finalized,
                'admin_finalized_at' => $handover->admin_finalized_at,
                'status' => $handover->status,
                'created_at' => $handover->created_at,
                'updated_at' => $handover->updated_at,
            ];

            return $this->sendSuccess('Handover finalized successfully', $data);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error finalizing Handover', ['error' => $e->getMessage()]);

            return $this->sendError('Error finalizing Handover.');
        }
    }
}
