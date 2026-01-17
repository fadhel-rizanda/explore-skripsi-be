<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllRequest;
use App\Http\Requests\PetRequest;
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
            $perPage = $request->query('per_page', 15);
            $search = $request->query('search');
            $typeOfAnimalId = $request->query('type_of_animal_id');
            $age = $request->query('age');
            $tagPersonalityId = $request->query('tag_personality_id');

            // Eager load relationships to avoid N+1 query issues
            $pets = Pet::with([
                'typeOfAnimal:id,name',
                'status:id,name',
                'personalityTags:id,name',
                'physiqueTags:id,name',
                'profilePictures:id,filename,mime_type,public_url,path',
                'additionalRecords:id,filename,mime_type,public_url,path',
            ])
                ->when($search, function ($q, $search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                })
                ->when($typeOfAnimalId, function ($q) use ($typeOfAnimalId) {
                    $q->where('type_of_animal_id', $typeOfAnimalId);
                })
                ->when($age !== null, function ($q) use ($age) {
                    $now = now();
                    if ($age === 'baby') {
                        // < 6 months
                        $q->where('date_of_birth', '>', $now->copy()->subMonths(6))
                            ->where('date_of_birth', '<=', $now);
                    } elseif ($age === 'young') {
                        // 6 months to < 1 year
                        $q->where('date_of_birth', '<=', $now->copy()->subMonths(6))
                            ->where('date_of_birth', '>', $now->copy()->subYear());
                    } elseif ($age === 'adult') {
                        // 1 year to < 7 years
                        $q->where('date_of_birth', '<=', $now->copy()->subYear())
                            ->where('date_of_birth', '>', $now->copy()->subYears(7));
                    } elseif ($age === 'senior') {
                        // >= 7 years
                        $q->where('date_of_birth', '<=', $now->copy()->subYears(7));
                    }
                })
                ->when($tagPersonalityId, function ($q) use ($tagPersonalityId) {
                    $q->whereHas('personalityTags', function ($subQuery) use ($tagPersonalityId) {
                        $subQuery->where('mt_all_tag.id', $tagPersonalityId);
                    });
                })
                ->orderBy('created_at', 'desc') // Default sorting
                ->paginate($perPage);

            // Transform data using map. For more complex transformations, consider using API Resources.
            $transformedData = $pets->getCollection()->map(function ($pet) {
                // Calculate age
                $dateOfBirth = $pet->date_of_birth;
                $ageInYears = $dateOfBirth->age; // Carbon's age property is simpler

                if ($ageInYears >= 1) {
                    $age = $ageInYears;
                    $ageUnit = $age === 1 ? 'year old' : 'years old';
                } else {
                    $age = $dateOfBirth->diffInMonths(now());
                    $ageUnit = $age === 1 ? 'month old' : 'months old';
                }

                return [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'status' => $pet->status->name,
                    'type_of_animal_id' => $pet->type_of_animal_id,
                    'type_of_animal_name' => $pet->typeOfAnimal->name,
                    'age' => $age,
                    'age_unit' => $ageUnit,
                    'tags_personality' => $pet->personalityTags->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ])->values(),
                    'tags_physique' => $pet->physiqueTags->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ])->values(),
                    'profile_pictures' => $pet->profilePictures->map(fn ($picture) => [
                        'id' => $picture->id,
                        'filename' => $picture->filename,
                        'mime_type' => $picture->mime_type,
                        'public_url' => $picture->public_url,
                        'path' => $picture->path,
                    ])->values(),
                    'additional_records' => $pet->additionalRecords->map(fn ($record) => [
                        'id' => $record->id,
                        'filename' => $record->filename,
                        'mime_type' => $record->mime_type,
                        'public_url' => $record->public_url,
                        'path' => $record->path,
                    ])->values(),
                ];
            });

            // Set transformed data back to collection
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
                $availableStatus = Status::where('name', 'available')->firstOrFail();

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
                $pet->update([
                    'type_of_animal_id' => $request->type_of_animal_id,
                    'size' => $request->size,
                    'name' => $request->name,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    'about' => $request->about,
                    'breed' => $request->breed,
                    'special_needs' => $request->special_needs,
                ]);

                // Sync physique tags if provided
                if ($request->filled('physique_ids')) {
                    $pet->physiqueTags()->sync($request->physique_ids);
                }

                // Sync personality tags if provided
                if ($request->filled('personality_ids')) {
                    $pet->personalityTags()->sync($request->personality_ids);
                }

                // Sync profile pictures if provided
                if ($request->filled('profile_picture_ids')) {
                    $pet->profilePictures()->sync($request->profile_picture_ids);
                }

                // Sync additional records if provided
                if ($request->filled('additional_record_ids')) {
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

}
