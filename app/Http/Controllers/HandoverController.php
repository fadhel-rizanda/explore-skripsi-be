<?php

namespace App\Http\Controllers;

use App\Enums\AdoptionStageEnum;
use App\Enums\AdoptionStatusEnum;
use App\Enums\ModelReferenceEnum;
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

    #[\Deprecated]
    public function createHandover(Adoption $adoption)
    {
        $handover = $adoption->handovers()
            ->with(['schedule', 'schedule.address', 'status'])
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($handover) {
            return $this->sendError('Handover already exists for this adoption.', 400);
        }

        $handover = $adoption->handovers()->create([
            'adoption_id' => $adoption->id,
            'status_id' => Status::getCache(
                StatusTypeEnum::ADOPTION->value,
                AdoptionStatusEnum::IN_PROGRESS->value
            )->id,
            'created_by' => auth('api')->id(),
        ]);

        return $this->sendSuccess('Handover created successfully', $handover);
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

            $handover = Handover::firstOrCreate(
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
                    referenceType: ModelReferenceEnum::ADOPTION_HANDOVER->value,
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

            return $this->sendSuccess(
                'Handover scheduled successfully',
                $handover->load(['meetNGreet.schedule.address', 'status'])
            );

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
                    referenceType: ModelReferenceEnum::ADOPTION_HANDOVER->value,
                    referenceId: $handover->id,
                )->notifyUsers(new AdoptionMailNotification(
                    action: AdoptionStageEnum::HANDOVER->value,
                    adoption: $adoption,
                    notes: 'The Handover Meet and Greet has been completed.'
                ))->getNotifications()->first();

                broadcast(new AdoptionUpdated($notification));
            }

            return $this->sendSuccess(
                'Meet and Greet approved successfully',
                $meetNGreet->load(['schedule.address', 'status'])
            );

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

            $handover->attachments()->sync(
                $request->input('attachment_ids')
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

            return $this->sendSuccess(
                'Handover evidence updated successfully',
                $handover->load(['attachments'])
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
                        'Adopted'
                    )->id,
                ]);
            }

            DB::commit();

            $notification = $this->notificationService->createBulk(
                userIds: [$adoption->adopter->id, $adoption->provider->id],
                title: 'Handover Finalized',
                message: 'The handover has been finalized for the adoption of ' . ($adoption->pet->name ?? 'Unnamed Pet'),
                referenceType: ModelReferenceEnum::ADOPTION_HANDOVER->value,
                referenceId: $handover->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: AdoptionStageEnum::HANDOVER->value,
                    adoption: $adoption,
                    notes: 'The handover has been finalized.'
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            return $this->sendSuccess(
                'Handover finalized successfully',
                $handover->load('status')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error finalizing Handover', ['error' => $e->getMessage()]);

            return $this->sendError('Error finalizing Handover.');
        }
    }
}
