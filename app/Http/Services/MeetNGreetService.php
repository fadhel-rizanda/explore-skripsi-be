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
    private const ADDRESS_FIELDS = [
        'street',
        'province_id',
        'regency_id',
        'district_id',
        'zip_code',
        'notes',
        'link',
    ];

    /**
     * Create or update a Meet & Greet schedule
     */
    public function createSchedule(Adoption $adoption, array $data, ?string $stage = null): MeetNGreet
    {
        $user = auth('api')->user();

        $adoption = Adoption::where('id', $adoption->id)
            ->lockForUpdate()
            ->with(['adopter', 'provider', 'status'])
            ->firstOrFail();

        if ($adoption->status->name !== AdoptionStatusEnum::NEED_AN_ACTION->value) {
            throw new \Exception('Adoption is not in a valid state to schedule Meet and Greet.');
        }

        $statusId = Status::getCache(
            StatusTypeEnum::ADOPTION->value,
            AdoptionStatusEnum::IN_PROGRESS->value
        )->id;

        $addressData = array_intersect_key(
            $data['address'] ?? [],
            array_flip(self::ADDRESS_FIELDS)
        );

        $address = Address::create($addressData);

        $scheduleData = array_filter([
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], fn ($value) => ! is_null($value));

        $schedule = Schedule::create([
            ...$scheduleData,
            'address_id' => $address->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $meetNGreet = MeetNGreet::create([
            'adoption_id' => $adoption->id,
            'schedule_id' => $schedule->id,
            'status_id' => $statusId,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'stage' => $stage ?? 'default',
        ]);

        return $this->autoConfirm($user, $adoption->adopter, $meetNGreet, $adoption->provider);
    }

    public function updateSchedule(Adoption $adoption, array $data, MeetNGreet $meetNGreet): MeetNGreet
    {
        $user = auth('api')->user();

        $adoption = Adoption::where('id', $adoption->id)
            ->lockForUpdate()
            ->with(['adopter', 'provider', 'status'])
            ->firstOrFail();

        $meetNGreet = MeetNGreet::where('id', $meetNGreet->id)
            ->lockForUpdate()
            ->with(['status', 'schedule.address'])
            ->firstOrFail();

        if ($adoption->status->name !== AdoptionStatusEnum::NEED_AN_ACTION->value || $meetNGreet->status->name !== AdoptionStatusEnum::IN_PROGRESS->value) {
            throw new \Exception('Adoption is not in a valid state to schedule Meet and Greet.');
        }

        $addressData = array_intersect_key(
            $data['address'] ?? [],
            array_flip(self::ADDRESS_FIELDS)
        );

        $meetNGreet->schedule->address->update($addressData);

        $scheduleData = array_filter([
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], fn ($value) => ! is_null($value));

        $meetNGreet->schedule->update([
            ...$scheduleData,
            'updated_by' => $user->id,
        ]);

        return $this->autoConfirm($user, $adoption->adopter, $meetNGreet, $adoption->provider);
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

    private function autoConfirm(\App\Models\User|\Illuminate\Contracts\Auth\Authenticatable|null $user, mixed $adopter, MeetNGreet $meetNGreet, mixed $provider): MeetNGreet
    {
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
    }
}
