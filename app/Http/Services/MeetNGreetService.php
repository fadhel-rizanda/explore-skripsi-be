<?php

namespace App\Http\Services;

use App\Enums\AdoptionStageEnum;
use App\Enums\AdoptionStatusEnum;
use App\Enums\StatusTypeEnum;
use App\Enums\TagTypeEnum;
use App\Models\Address;
use App\Models\Adoption;
use App\Models\AllTag;
use App\Models\MeetNGreet;
use App\Models\Schedule;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class MeetNGreetService
{
    /**
     * Create or update a Meet & Greet schedule
     */
    public function schedule(Adoption $adoption, array $data, ?string $meetNGreetId = null): MeetNGreet
    {
        $user = auth('api')->user();
        $adopter = $adoption->adopter;
        $provider = $adoption->provider;
        $statusId = Status::getCache(
            StatusTypeEnum::ADOPTION->value,
            AdoptionStatusEnum::IN_PROGRESS->value
        )->id;

        return DB::transaction(function () use ($adoption, $adopter, $provider, $data, $meetNGreetId, $user, $statusId) {

            $meetNGreet = $adoption->meetNGreets()
                ->with(['schedule', 'schedule.address'])
                ->find($meetNGreetId);

            if ($adoption->status->name !== AdoptionStatusEnum::NEED_AN_ACTION->value || ($meetNGreet && $meetNGreet->status->name !== AdoptionStatusEnum::IN_PROGRESS->value)) {
                throw new \Exception('Adoption is not in a valid state to schedule Meet and Greet.');
            }

            $addressData = array_intersect_key($data['address'] ?? [], array_flip(['street', 'city', 'state', 'zip_code', 'country', 'notes', 'link']));
            $addressData['updated_by'] = $user->id;

            $scheduleData = ['scheduled_time' => $data['scheduled_time']];
            if (isset($data['notes'])) {
                $scheduleData['notes'] = $data['notes'];
            }
            $scheduleData['updated_by'] = $user->id;

            if ($meetNGreet) {
                $meetNGreet->schedule?->address?->update($addressData);
                $meetNGreet->schedule?->update($scheduleData);
                $meetNGreet->update([
                    'status_id' => $statusId,
                    'updated_by' => $user->id,
                ]);
            } else {
                $address = Address::create(array_merge($addressData, ['created_by' => $user->id]));
                $scheduleData['address_id'] = $address->id;
                $scheduleData['created_by'] = $user->id;
                $schedule = Schedule::create($scheduleData);

                $meetNGreet = MeetNGreet::create([
                    'adoption_id' => $adoption->id,
                    'schedule_id' => $schedule->id,
                    'status_id' => $statusId,
                    'created_by' => $user->id,
                ]);
            }

            $updateData = [];
            if ($user->id === $adopter->id && ! $meetNGreet->adopter_confirmed) {
                $updateData['adopter_confirmed'] = true;
                $updateData['adopter_confirmed_at'] = now();
            } elseif ($user->id === $provider->id && ! $meetNGreet->provider_confirmed) {
                $updateData['provider_confirmed'] = true;
                $updateData['provider_confirmed_at'] = now();
            }
            if (! empty($updateData)) {
                $meetNGreet->update($updateData);
            }

            return $meetNGreet;
        });
    }

    /**
     * Approve a Meet & Greet schedule (adopter/provider confirmation)
     */
    public function approve(Adoption $adoption, MeetNGreet $meetNGreet): MeetNGreet
    {
        $user = auth('api')->user();
        $adopter = $adoption->adopter;
        $provider = $adoption->provider;

        if ($meetNGreet->adoption_id !== $adoption->id) {
            throw new \Exception('Meet and Greet does not belong to this adoption.');
        }
        if (
            $adoption->status->name !== AdoptionStatusEnum::NEED_AN_ACTION->value
            || $meetNGreet->status->name !== AdoptionStatusEnum::IN_PROGRESS->value
        ) {
            throw new \Exception('Adoption is not in a valid state to approve Meet and Greet.');
        }

        $statusId = Status::getCache(
            StatusTypeEnum::ADOPTION->value,
            AdoptionStatusEnum::IN_PROGRESS->value
        )->id;

        return DB::transaction(function () use ($adoption, $meetNGreet, $user, $adopter, $provider, $statusId) {

            $updateData = [];
            if ($user->id === $adopter->id && ! $meetNGreet->adopter_confirmed) {
                $updateData['adopter_confirmed'] = true;
                $updateData['adopter_confirmed_at'] = now();
            }
            if ($user->id === $provider->id && ! $meetNGreet->provider_confirmed) {
                $updateData['provider_confirmed'] = true;
                $updateData['provider_confirmed_at'] = now();
            }
            if (! empty($updateData)) {
                $meetNGreet->update($updateData);
            }

            $meetNGreet->refresh();

            if ($meetNGreet->adopter_confirmed && $meetNGreet->provider_confirmed) {
                $adoption->update([
                    'status_id' => $statusId,
                    'updated_by' => $user->id,
                ]);
            }

            return $meetNGreet;
        });
    }

    /**
     * Finalize a Meet & Greet
     */
    public function finalize(Adoption $adoption, MeetNGreet $meetNGreet): MeetNGreet
    {
        $user = auth('api')->user();
        if ($meetNGreet->adoption_id !== $adoption->id) {
            throw new \Exception('Meet and Greet does not belong to this adoption.');
        }
        if (! ($meetNGreet->adopter_confirmed && $meetNGreet->provider_confirmed)) {
            throw new \Exception('Both parties must confirm the Meet and Greet before finalizing.');
        }
        if ($adoption->status->name !== AdoptionStatusEnum::IN_PROGRESS->value && $meetNGreet->status->name !== AdoptionStatusEnum::IN_PROGRESS->value) {
            throw new \Exception('Adoption is not in a valid state to finalize Meet and Greet.');
        }

        return DB::transaction(function () use ($adoption, $meetNGreet, $user) {
            $meetNGreet->update([
                'status_id' => Status::getCache(
                    StatusTypeEnum::ADOPTION->value,
                    AdoptionStatusEnum::COMPLETED->value
                )->id,
                'updated_by' => $user->id,
                'is_active' => false,
            ]);

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

            return $meetNGreet;
        });
    }
}
