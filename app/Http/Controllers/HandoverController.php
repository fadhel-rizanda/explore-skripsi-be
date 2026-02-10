<?php

namespace App\Http\Controllers;

use App\Enums\AdoptionStageEnum;
use App\Enums\AdoptionStatusEnum;
use App\Enums\ModelFlagEnum;
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
use App\Models\Status;
use App\Notifications\AdoptionMailNotification;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

class HandoverController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private NotificationService $notificationService,
        private MeetNGreetService $meetNGreetService
    ) {}

    public function handover(Adoption $adoption)
    {
        $handover = $adoption->handovers()
            ->with(['meetNGreet.schedule', 'meetNGreet.schedule.address', 'status'])
            ->orderBy('updated_at', 'desc')
            ->first();

        return $this->sendSuccess('Handover fetched successfully', $handover);
    }

    public function purposeMeetNGreetSchedule(Adoption $adoption, CreateScheduleRequest $request)
    {
        $user = auth('api')->user();

        try {
            DB::beginTransaction();
            $meetNGreet = $this->meetNGreetService->schedule(
                adoption: $adoption,
                data: $request->validated(),
                meetNGreetId: $request->input('meet_n_greet_id')
            );

            $handover = Handover::updateOrCreate(
                [
                    'adoption_id' => $adoption->id,
                ],
                [
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
                    referenceType: ModelFlagEnum::ADOPTION_HANDOVER->value,
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
                    referenceType: ModelFlagEnum::ADOPTION_HANDOVER->value,
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

            $handover->syncAttachmentsWithMetadata(
                relation: 'attachments',
                newIds: $request->input('attachment_ids'),
                modelReference: ModelReferenceEnum::HANDOVER->value,
            );

            $adopterAttached = $handover->attachments()
                ->where('mt_attachment.uploaded_by', $adoption->adopter->id)
                ->exists();
            $providerAttached = $handover->attachments()
                ->where('mt_attachment.uploaded_by', $adoption->provider->id)
                ->exists();

            if ($adopterAttached && $providerAttached && $handover->status->name === AdoptionStatusEnum::IN_PROGRESS->value && $adoption->status->name === AdoptionStatusEnum::IN_PROGRESS->value) {
                $adoption->update([
                    'status_id' => Status::getCache(
                        StatusTypeEnum::ADOPTION->value,
                        AdoptionStatusEnum::NEED_AN_ACTION->value
                    )->id,
                ]);
            }

            DB::commit();

            $data = [
                'id' => $handover->id,
                'adoption_id' => $handover->adoption_id,
                'status' => $handover->status,
                'attachments' => $handover->attachments,
                'created_at' => $handover->created_at,
                'updated_at' => $handover->updated_at,
            ];

            return $this->sendSuccess(
                'Handover evidence updated successfully',
                $data
            );

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

            if ($user->id === $adopter->id && ! $handover->adopter_finalized) {
                $updateData['adopter_finalized'] = true;
                $updateData['adopter_finalized_at'] = now();
            }

            if ($user->id === $provider->id && ! $handover->provider_finalized) {
                $updateData['provider_finalized'] = true;
                $updateData['provider_finalized_at'] = now();
            }

            if (
                $user->hasRole(RoleEnum::ADMIN->value) && ! $handover->admin_finalized && $handover->adopter_finalized && $handover->provider_finalized) {
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
                referenceType: ModelFlagEnum::ADOPTION_HANDOVER->value,
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
