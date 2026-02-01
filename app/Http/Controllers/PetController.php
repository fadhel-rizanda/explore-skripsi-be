<?php

namespace App\Http\Controllers;

use App\Enums\PetStatusEnum;
use App\Enums\StatusTypeEnum;
use App\Http\Requests\GetAllRequest;
use App\Http\Requests\PetRequest;
use App\Http\Resources\PetMonitorResource;
use App\Models\Pet;
use App\Models\Status;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PetController extends Controller
{
    use ResponseAPI;

    /**
     * Display a listing of the pets.
     */
    public function index(GetAllRequest $request)
    {
        try {
            $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
            
            $perPage = $request->query('per_page', 15);
            $pets = $this->buildPetQuery($request, $isAdmin)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if ($isAdmin) {
                return $this->sendSuccessPagination(
                    'Monitor pet list retrieved successfully',
                    PetMonitorResource::collection($pets)
                );
            }

            $transformedData = $pets->getCollection()->map(function ($pet) {
                $dateOfBirth = $pet->date_of_birth;
                $ageInYears = $dateOfBirth->age;
                if ($ageInYears >= 1) {
                    $age = $ageInYears;
                    $ageUnit = $age === 1 ? 'year old' : 'years old';
                } else {
                    $age = (int) $dateOfBirth->diffInMonths(now());
                    $ageUnit = $age === 1 ? 'month old' : 'months old';
                }

                $profilePicture = $pet->profilePicture->first();
                $profilePictureData = $profilePicture ? $profilePicture->public_url : null;

                return [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'type_of_animal_id' => $pet->type_of_animal_id,
                    'type_of_animal_name' => $pet->typeOfAnimal->name,
                    'age' => $age,
                    'age_unit' => $ageUnit,
                    'profile_picture' => $profilePictureData,
                ];
            });

            $pets->setCollection($transformedData);

            return $this->sendSuccessPagination(
                'Animals retrieved successfully',
                $pets
            );

        } catch (\Exception $e) {
            Log::error('Failed to retrieve pets: ' . $e->getMessage());

            return $this->sendError(
                config('app.debug') ? $e->getMessage() : 'Failed to retrieve pets',
                500
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PetRequest $request)
    {
        try {
            $pet = DB::transaction(function () use ($request) {
                $availableStatus = Status::getCache(StatusTypeEnum::PET->value, PetStatusEnum::AVAILABLE->value);

                $pet = Pet::create([
                    'user_id' => auth('api')->user()->id,
                    'type_of_animal_id' => $request->type_of_animal_id,
                    'size' => $request->size,
                    'name' => $request->name,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    'about' => $request->about,
                    'breed' => $request->breed,
                    'special_needs' => $request->special_needs,
                    'status_id' => $availableStatus->id,
                ]);

                // Attach physique tags if provided
                if ($request->filled('physique_ids')) {
                    $pet->physiqueTags()->sync($request->physique_ids);
                }

                // Attach personality tags if provided
                if ($request->filled('personality_ids')) {
                    $pet->personalityTags()->sync($request->personality_ids);
                }

                // Attach profile pictures if provided
                if ($request->filled('profile_picture_ids')) {
                    $pet->profilePictures()->sync($request->profile_picture_ids);
                }

                // Attach profile pictures if provided
                if ($request->filled('additional_record_ids')) {
                    $pet->additionalRecords()->sync($request->additional_record_ids);
                }

                return $pet;
            });

            return $this->sendSuccess('Pet created successfully', $pet, 201);

        } catch (\Exception $e) {
            Log::error('Pet creation failed: ' . $e->getMessage());

            return $this->sendError(
                config('app.debug') ? $e->getMessage() : 'Failed to create pet',
                500
            );
        }
    }

    /**
     * Update the specified pet in storage.
     */
    public function update(PetRequest $request, $id)
    {
        try {
            $pet = Pet::findOrFail($id);

            DB::transaction(function () use ($request, $pet) {
                $pet->update($request->validated());

                if ($request->has('physique_ids')) {
                    $pet->physiqueTags()->sync($request->physique_ids);
                }

                // Sync personality tags if provided
                if ($request->has('personality_ids')) {
                    $pet->personalityTags()->sync($request->personality_ids);
                }

                // Sync profile pictures if provided
                if ($request->has('profile_picture_ids')) {
                    $pet->profilePictures()->sync($request->profile_picture_ids);
                }

                // Sync additional records if provided
                if ($request->has('additional_record_ids')) {
                    $pet->additionalRecords()->sync($request->additional_record_ids);
                }
            });

            return $this->sendSuccess('Pet updated successfully', $pet);

        } catch (\Exception $e) {
            Log::error('Pet update failed: ' . $e->getMessage());

            return $this->sendError(
                config('app.debug') ? $e->getMessage() : 'Failed to update pet',
                500
            );
        }
    }

    /**
     * Display the specified pet detail.
     */
    public function show($id)
    {
        try {
            $pet = Pet::with([
                'typeOfAnimal:id,name',
                'profilePictures:id,public_url',
                'physiqueTags:id,name',
                'personalityTags:id,name',
                'additionalRecords:id,public_url,filename,mime_type,path',
            ])->findOrFail($id);

            $data = [
                'type_of_animal_id' => $pet->type_of_animal_id,
                'size' => $pet->size,
                'name' => $pet->name,
                'date_of_birth' => $pet->date_of_birth?->toDateString(),
                'gender' => $pet->gender,
                'about' => $pet->about,
                'breed' => $pet->breed,
                'profile_pictures' => $pet->profilePictures->map(function($picture) {
                    return [
                        'id' => $picture->id,
                        'public_url' => $picture->public_url,
                    ];
                }),
                'special_needs' => $pet->special_needs,
                'physique_tags' => $pet->physiqueTags->map(function($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                }),
                'personality_tags' => $pet->personalityTags->map(function($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                }),
                'additional_records' => $pet->additionalRecords->map(function($record) {
                    return [
                        'id' => $record->id,
                        'public_url' => $record->public_url,
                        'filename' => $record->filename,
                        'mime_type' => $record->mime_type,
                        'path' => $record->path,
                    ];
                }),
            ];

            return $this->sendSuccess('Pet detail retrieved successfully', $data);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Pet not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve pet detail: ' . $e->getMessage());

            return $this->sendError(
                config('app.debug') ? $e->getMessage() : 'Failed to retrieve pet detail',
                500
            );
        }
    }

    /**
     * Remove the specified pet from storage.
     */
    public function destroy($id)
    {
        try {
            $pet = Pet::findOrFail($id);

            $user = auth('api')->user();
            if (! $user->hasRole('admin') && $pet->user_id !== $user->id) {
                return $this->sendError('You are not authorized to delete this pet.', 403);
            }

            $pet->delete();

            return $this->sendSuccess('Pet deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Pet not found', 404);
        } catch (\Exception $e) {
            Log::error("Error deleting pet ID {$id}: " . $e->getMessage());

            return $this->sendError('Internal server error', 500);
        }
    }

    /**
     * Build base pet query with common filters.
     */
    private function buildPetQuery(GetAllRequest $request, bool $isAdmin)
    {
        $search = $request->query('search');
        $typeOfAnimalId = $request->query('type_of_animal_id');
        $age = $request->query('age');
        $tagPersonalityId = $request->query('tag_personality_id');

        return Pet::with('typeOfAnimal:id,name')->when(! $isAdmin, fn ($q) => $q->with('profilePicture:id,public_url'))
            ->when(! $isAdmin, fn ($q) => $q->where('is_active', true))
            ->when($search, function ($q) use ($search, $isAdmin) {
                $q->where(function ($query) use ($search, $isAdmin) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
                    if ($isAdmin && \Illuminate\Support\Str::isUuid($search)) {
                        $query->orWhere('id', $search);
                    }
                });
            })
            ->when($typeOfAnimalId, function ($q) use ($typeOfAnimalId) {
                $q->where('type_of_animal_id', $typeOfAnimalId);
            })
            ->when($age !== null, function ($q) use ($age) {
                $now = now();
                if ($age === 'baby') {
                    $q->where('date_of_birth', '>', $now->copy()->subMonths(6))
                        ->where('date_of_birth', '<=', $now);
                } elseif ($age === 'young') {
                    $q->where('date_of_birth', '<=', $now->copy()->subMonths(6))
                        ->where('date_of_birth', '>', $now->copy()->subYear());
                } elseif ($age === 'adult') {
                    $q->where('date_of_birth', '<=', $now->copy()->subYear())
                        ->where('date_of_birth', '>', $now->copy()->subYears(7));
                } elseif ($age === 'senior') {
                    $q->where('date_of_birth', '<=', $now->copy()->subYears(7));
                }
            })
            ->when($tagPersonalityId, function ($q) use ($tagPersonalityId) {
                $q->whereHas('personalityTags', function ($subQuery) use ($tagPersonalityId) {
                    $subQuery->where('mt_all_tag.id', $tagPersonalityId);
                });
            });
    }
}
