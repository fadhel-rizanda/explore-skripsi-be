<?php

namespace App\Http\Controllers;

use App\Http\Requests\PetRequest;
use App\Models\Pet;
use App\Models\PetPersonalityTag;
use App\Models\PetPhysiqueTag;
use App\Models\PetProfilePicture;
use App\Models\Status;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PetController extends Controller
{
    use ResponseAPI;

    /**
     * Display a listing of the pets.
     */
    public function index(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);

            // Eager load relationships to avoid N+1 query issues
            $pets = Pet::with(['typeOfAnimal', 'personalityTags'])->paginate($limit, ['*'], 'page', $page);

            // Transform data using map. For more complex transformations, consider using API Resources.
            $transformedData = $pets->getCollection()->map(function ($pet) {
                $personalityTag = $pet->personalityTags->first();

                // Calculate age
                $dateOfBirth = \Carbon\Carbon::parse($pet->date_of_birth);
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
                    'type_of_animal_id' => $pet->type_of_animal_id,
                    'type_of_animal_name' => $pet->typeOfAnimal->tag_name,
                    'age' => $age,
                    'age_unit' => $ageUnit,
                    'tags_personality_id' => $personalityTag->id ?? null,
                    'tags_personality_name' => $personalityTag->tag_name ?? null,
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
                // Create pet
                $petData = $request->validated();
                $petData['id'] = Str::uuid();
                
                // Auto-set status_id to "available" (fetched from the database)
                $availableStatus = Status::where('status_name', 'available')->firstOrFail();
                
                $petData['status_id'] = $availableStatus->id;
                
                $pet = Pet::create($petData);

                // Attach physique tags if provided
                if ($request->has('physique_ids')) {
                    $physiqueRecords = collect($request->physique_ids)->map(function ($physiqueId) use ($pet) {
                        return [
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'all_tag_id' => $physiqueId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    })->all();
                    PetPhysiqueTag::insert($physiqueRecords);
                }

                // Attach personality tags if provided
                if ($request->has('personality_ids')) {
                    $personalityRecords = collect($request->personality_ids)->map(function ($personalityId) use ($pet) {
                        return [
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'all_tag_id' => $personalityId
                        ];
                    })->all();
                    PetPersonalityTag::insert($personalityRecords);
                }

                // Attach profile pictures if provided
                if ($request->has('profile_picture_ids')) {
                    $profilePictureRecords = collect($request->profile_picture_ids)->map(function ($attachmentId) use ($pet) {
                        return [
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'attachment_id' => $attachmentId
                        ];
                    })->all();
                    PetProfilePicture::insert($profilePictureRecords);
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
}
