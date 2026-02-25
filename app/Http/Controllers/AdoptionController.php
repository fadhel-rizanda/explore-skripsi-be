<?php

namespace App\Http\Controllers;

use App\Enums\AdoptionStageEnum;
use App\Enums\AdoptionStatusEnum;
use App\Enums\ModelReferenceEnum;
use App\Enums\PetStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Enums\TagTypeEnum;
use App\Events\AdoptionUpdated;
use App\Http\Requests\GetAllRequest;
use App\Http\Resources\AdoptionResource;
use App\Http\Services\NotificationService;
use App\Models\Adoption;
use App\Models\AllTag;
use App\Models\Pet;
use App\Models\Status;
use App\Notifications\AdoptionMailNotification;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdoptionController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function listAdoptions(GetAllRequest $request)
    {
        try {
            $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
            $paginator = $this->getAdoptionsQuery($request, $isAdmin);

            $adoptions = AdoptionResource::collection($paginator->items());
            return $this->sendSuccessPagination(
                'Adoptions retrieved successfully.',
                $paginator,
                $adoptions
            );
        } catch (\Throwable $e) {
            \Log::error('Error fetching adoptions', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching adoptions.');
        }
    }

    public function adoptionDetail(Adoption $adoption)
    {
        try {
            $adoption->load([
                'pet:id,name',
                'provider:mt_user.id,mt_user.name,mt_user.email,mt_user.avatar',
                'provider.attachment:id,public_url',
                'adopter:mt_user.id,mt_user.name,mt_user.email,mt_user.avatar,mt_user.is_active',
                'adopter.attachment:id,public_url',
                'status:id,name,color_code',
                'stageTag:id,name',
                'latestMeetNGreet',
                'latestMeetNGreet.schedule',
                'latestHandover',
                'latestHandover.meetNGreet',
                'latestHandover.meetNGreet.schedule',
            ]);

            return $this->sendSuccess(
                'Adoption details retrieved successfully.',
                new AdoptionResource($adoption)
            );
        } catch (\Throwable $e) {
            \Log::error('Error fetching adoption details', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching adoption details.');
        }
    }

    public function adopt(Pet $pet)
    {
        try {
            DB::beginTransaction();

            $pet = Pet::where('id', $pet->id)
                ->lockForUpdate()
                ->with(['address', 'user.address', 'status'])
                ->firstOrFail();

            $user = auth('api')->user()->load('address');

            if ($pet->status->name !== PetStatusEnum::AVAILABLE->value) {
                return $this->sendError('This pet is not available for adoption.');
            }

            $petRegencyId = $pet->effective_address?->regency_id;
            $userRegencyId = $user->address?->regency_id;

            if (! $petRegencyId || ! $userRegencyId) {
                return $this->sendError('Both adopter and pet must have a valid regency address.');
            }

            if ($petRegencyId !== $userRegencyId) {
                return $this->sendError('You can only adopt pets within the same regency.');
            }

            $adoption = Adoption::create([
                'adopter_id' => $user->id,
                'pet_id' => $pet->id,
                'status_id' => Status::getCache(StatusTypeEnum::ADOPTION->value, AdoptionStatusEnum::NEED_AN_ACTION->value)->id,
                'stage_tag_id' => AllTag::getCache(TagTypeEnum::ADOPTION_STAGE->value, AdoptionStageEnum::REQUIREMENT->value)->id,
                'updated_by' => $user->id,
            ]);

            $pet->update([
                'status_id' => Status::getCache(StatusTypeEnum::PET->value, PetStatusEnum::PENDING->value)->id,
            ]);

            DB::commit();

            $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'New Adoption Application Submitted',
                message: 'An adoption application has been submitted for the pet: ' . ($pet->name ?? 'Unnamed Pet'),
                referenceType: ModelReferenceEnum::ADOPTION->value,
                referenceId: $adoption->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: AdoptionStageEnum::APPLICATION_SUBMITTED->value,
                    adoption: $adoption,
                    notes: 'An adoption application has been submitted.'
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            return $this->sendSuccess(
                'Adoption application created successfully.',
                new AdoptionResource($adoption)
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error creating adoption application', ['error' => $e->getMessage()]);

            return $this->sendError('Error creating adoption application.');
        }
    }

    public function reject(Pet $pet, Adoption $adoption)
    {
        return $this->terminateAdoption(
            $pet,
            $adoption,
            AdoptionStatusEnum::REJECTED,
            'rejected',
        );
    }

    public function cancel(Pet $pet, Adoption $adoption)
    {
        return $this->terminateAdoption(
            $pet,
            $adoption,
            AdoptionStatusEnum::CANCELLED,
            'cancelled',
        );
    }

    private function terminateAdoption(Pet $pet, Adoption $adoption, AdoptionStatusEnum $status, string $action)
    {
        $user = auth('api')->user();

        if ($adoption->pet_id !== $pet->id) {
            return $this->sendError('The adoption application does not belong to this pet.', 400);
        }

        $restrictedStatuses = [
            AdoptionStageEnum::HANDOVER->value,
            AdoptionStatusEnum::COMPLETED->value,
            AdoptionStatusEnum::REJECTED->value,
            AdoptionStatusEnum::CANCELLED->value,
        ];

        $isRestricted = in_array($adoption->stageTag->name, $restrictedStatuses)
            || in_array($adoption->status->name, $restrictedStatuses);

        if ($isRestricted && ! $user->hasRole(RoleEnum::ADMIN->value)) {
            return $this->sendError("This adoption application cannot be {$action} at its current stage.");
        }

        try {
            DB::beginTransaction();

            $adoption->update([
                'status_id' => Status::getCache(StatusTypeEnum::ADOPTION->value, $status->value)->id,
                'stage_tag_id' => AllTag::getCache(TagTypeEnum::ADOPTION_STAGE->value, $status->value)->id,
                'updated_by' => $user->id,
                'is_active' => false,
            ]);

            $pet->update([
                'status_id' => Status::getCache(StatusTypeEnum::PET->value, PetStatusEnum::AVAILABLE->value)->id,
            ]);

            DB::commit();

            $usersToNotify = [$adoption->adopter->id, $adoption->provider->id];
            $notification = $this->notificationService->createBulk(
                userIds: $usersToNotify,
                title: 'Adoption Application ' . ucfirst($action),
                message: 'The adoption application for the pet: ' . ($adoption->pet->name ?? 'Unnamed Pet') . " has been {$action}.",
                referenceType: ModelReferenceEnum::ADOPTION->value,
                referenceId: $adoption->id,
            )->notifyUsers(
                new AdoptionMailNotification(
                    action: $status->value,
                    adoption: $adoption,
                    notes: "The adoption application has been {$action}."
                )
            )->getNotifications()->first();
            broadcast(new AdoptionUpdated($notification));

            return $this->sendSuccess(
                "Adoption application {$action} successfully.",
                new AdoptionResource($adoption)
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Error {$action} adoption application", ['error' => $e->getMessage()]);

            return $this->sendError("Error {$action} adoption application.");
        }
    }

    private function getAdoptionsQuery(GetAllRequest $request, bool $isAdmin)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'created_at');
        $statusId = $request->query('status_id');
        $stageId = $request->query('stage_tag_id');
        $userId = auth('api')->id();

        $allowedSorts = ['created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $adoptions = Adoption::with([
            'pet:id,name',
            'provider:mt_user.id,mt_user.name,mt_user.email,mt_user.avatar',
            'provider.attachment:id,public_url',
            'adopter:mt_user.id,mt_user.name,mt_user.email,mt_user.avatar,mt_user.is_active',
            'adopter.attachment:id,public_url',
            'status:id,name,color_code',
            'stageTag:id,name,color_code',
        ])
            ->when(! $isAdmin, fn ($q) => $q->where(function ($query) use ($userId) {
                $query->where('adopter_id', $userId)
                    ->orWhereHas('pet', fn ($p) => $p->where('user_id', $userId));
            }))
            ->when($search, function ($q) use ($search, $isAdmin) {
                $q->where(function ($query) use ($search, $isAdmin) {
                    $query
                        ->whereHas('pet', fn ($p) => $p->where('name', 'ILIKE', "%{$search}%"))
                        ->orWhereHas('adopter', fn ($u) => $u->where('name', 'ILIKE', "%{$search}%"));

                    if ($isAdmin && Str::isUuid($search)) {
                        $query->orWhere('id', $search);
                    }
                });
            })
            ->when($statusId, fn ($q) => $q->where('status_id', $statusId))
            ->when($stageId, fn ($q) => $q->where('stage_tag_id', $stageId))
            ->orderBy($sortBy, 'desc')
            ->paginate($perPage);

        return $adoptions;
    }
}
