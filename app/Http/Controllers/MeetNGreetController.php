<?php

namespace App\Http\Controllers;

use App\Enums\AdoptionStageEnum;
use App\Enums\ModelReferenceEnum;
use App\Events\AdoptionUpdated;
use App\Http\Requests\CreateScheduleRequest;
use App\Http\Services\MeetNGreetService;
use App\Http\Services\NotificationService;
use App\Models\Adoption;
use App\Models\MeetNGreet;
use App\Notifications\AdoptionMailNotification;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

class MeetNGreetController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private MeetNGreetService   $meetNGreetService,
        private NotificationService $notificationService
    )
    {
    }

    public function meetNGreet(Adoption $adoption)
    {
        $meetNGreet = $adoption->meetNGreets()
            ->where('stage', 'default')
            ->with(['schedule', 'schedule.address', 'status'])
            ->orderBy('updated_at', 'desc')
            ->first();

        return $this->sendSuccess('Meet and Greet fetched successfully', $meetNGreet);
    }

    public function createSchedule(Adoption $adoption, CreateScheduleRequest $request)
    {
        try {
            DB::beginTransaction();
            $meetNGreet = $this->meetNGreetService->createSchedule(
                adoption: $adoption,
                data: $request->validated(),
            );

            DB::commit();

            $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'Meet and Greet Scheduled',
                message: 'A Meet and Greet has been scheduled for the adoption of ' . ($adoption->pet->name ?? 'Unnamed Pet'),
                referenceType: ModelReferenceEnum::MEETNGREET->value,
                referenceId: $meetNGreet->id,
            )->notifyUsers(new AdoptionMailNotification(
                action: AdoptionStageEnum::MEET_N_GREET->value,
                adoption: $adoption,
                notes: 'A Meet and Greet has been scheduled',
            ))->getNotifications()->first();

            broadcast(new AdoptionUpdated($notification));

            $data = [
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
            ];

            return $this->sendSuccess('Meet and Greet scheduled successfully', $data);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error scheduling Meet and Greet', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Error scheduling Meet and Greet.');
        }
    }

    public function updateSchedule(Adoption $adoption, MeetNGreet $meetNGreet, CreateScheduleRequest $request)
    {
        try {
            DB::beginTransaction();
            $meetNGreet = $this->meetNGreetService->updateSchedule(
                adoption: $adoption,
                data: $request->validated(),
                meetNGreet: $meetNGreet,
            );

            DB::commit();

            $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'Meet and Greet Scheduled',
                message: 'A Meet and Greet has been updated for the adoption of ' . ($adoption->pet->name ?? 'Unnamed Pet'),
                referenceType: ModelReferenceEnum::MEETNGREET->value,
                referenceId: $meetNGreet->id,
            )->notifyUsers(new AdoptionMailNotification(
                action: AdoptionStageEnum::MEET_N_GREET->value,
                adoption: $adoption,
                notes: 'A Meet and Greet has been updated',
            ))->getNotifications()->first();

            broadcast(new AdoptionUpdated($notification));

            $data = [
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
            ];

            return $this->sendSuccess('Meet and Greet updated successfully', $data);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error scheduling Meet and Greet', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Error scheduling Meet and Greet.');
        }
    }

    public function approveSchedule(Adoption $adoption, MeetNGreet $meetNGreet)
    {
        try {
            $meetNGreet = $this->meetNGreetService->approve(
                adoption: $adoption,
                meetNGreet: $meetNGreet
            );

            if ($meetNGreet->adopter_confirmed && $meetNGreet->provider_confirmed) {
                $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];
                $notification = $this->notificationService->createBulk(
                    userIds: $usersToNotify,
                    title: 'Meet and Greet Completed',
                    message: 'The Meet and Greet has been completed for the adoption of ' . ($adoption->pet->name ?? 'Unnamed Pet'),
                    referenceType: ModelReferenceEnum::MEETNGREET->value,
                    referenceId: $meetNGreet->id,
                )->notifyUsers(new AdoptionMailNotification(
                    action: AdoptionStageEnum::MEET_N_GREET->value,
                    adoption: $adoption,
                    notes: 'The Meet and Greet has been completed.'
                ))->getNotifications()->first();

                broadcast(new AdoptionUpdated($notification));
            }

            $data = [
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
            ];

            return $this->sendSuccess('Meet and Greet status updated successfully', $data);
        } catch (\Throwable $e) {
            \Log::error('Error updating Meet and Greet status', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Error updating Meet and Greet status.');
        }
    }

    public function finalizeMeetNGreet(Adoption $adoption, MeetNGreet $meetNGreet)
    {
        try {
            $meetNGreet = $this->meetNGreetService->finalize(
                adoption: $adoption,
                meetNGreet: $meetNGreet
            );

            $data = [
                'id' => $meetNGreet->id,
                'adoption_id' => $meetNGreet->adoption_id,
                'status' => $meetNGreet->status,
                'created_at' => $meetNGreet->created_at,
                'updated_at' => $meetNGreet->updated_at,
            ];

            return $this->sendSuccess('Meet and Greet finalized successfully', $data);
        } catch (\Throwable $e) {
            \Log::error('Error finalizing Meet and Greet', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Error finalizing Meet and Greet.');
        }
    }
}
