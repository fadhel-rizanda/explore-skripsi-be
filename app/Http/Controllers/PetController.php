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

            // Query pets dengan join ke tabel terkait
            $pets = Pet::select(
                'tr_pet.id',
                'tr_pet.name',
                'tr_pet.type_of_animal_id',
                'tr_pet.date_of_birth',
                'type_tag.tag_name as type_of_animal_name'
            )
                ->leftJoin('mt_all_tag as type_tag', 'tr_pet.type_of_animal_id', '=', 'type_tag.id')
                ->paginate($limit, ['*'], 'page', $page);

            // Transform data untuk menambahkan personality tags dan age calculation
            $transformedData = $pets->getCollection()->map(function ($pet) {
                // Get personality tags
                $personalityTag = PetPersonalityTag::with('allTag')
                    ->where('pet_id', $pet->id)
                    ->first();
                
                $personalityTags = $personalityTag ? (object) [
                    'tags_personality_id' => $personalityTag->allTag->id,
                    'tags_personality_name' => $personalityTag->allTag->tag_name,
                ] : null;

                // Calculate age
                $dateOfBirth = \Carbon\Carbon::parse($pet->date_of_birth);
                $now = \Carbon\Carbon::now();
                
                // Calculate difference
                $years = $dateOfBirth->diffInYears($now);
                $months = $dateOfBirth->diffInMonths($now) % 12; // Sisa bulan setelah dikurangi tahun

                if ($years >= 1) {
                    $age = (int) $years;
                    $ageUnit = $years === 1 ? 'year old' : 'years old';
                } else {
                    $age = (int) $months;
                    $ageUnit = $months === 1 ? 'month old' : 'months old';
                }

                return [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'type_of_animal_id' => $pet->type_of_animal_id,
                    'type_of_animal_name' => $pet->type_of_animal_name,
                    'age' => $age,
                    'age_unit' => $ageUnit,
                    'tags_personality_id' => $personalityTags->tags_personality_id ?? null,
                    'tags_personality_name' => $personalityTags->tags_personality_name ?? null,
                ];
            });

            // Set transformed data back to collection
            $pets->setCollection($transformedData);

            // Build response dengan meta pagination
            $response = [
                'status' => 'success',
                'message' => 'Animals retrieved successfully',
                'data' => $pets->items(),
                'meta' => [
                    'page' => $pets->currentPage(),
                    'limit' => $pets->perPage(),
                    'total_items' => $pets->total(),
                    'total_pages' => $pets->lastPage(),
                ],
            ];

            return response()->json($response, 200);

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
                
                // Auto-set status_id ke "available" (ambil dari database)
                $availableStatus = Status::where('status_name', 'available')->firstOrFail();
                
                $petData['status_id'] = $availableStatus->id;
                
                $pet = Pet::create($petData);

                // Attach physique tags if provided
                if ($request->has('physique_ids')) {
                    foreach ($request->physique_ids as $physiqueId) {
                        PetPhysiqueTag::create([
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'all_tag_id' => $physiqueId
                        ]);
                    }
                }

                // Attach personality tags if provided
                if ($request->has('personality_ids')) {
                    foreach ($request->personality_ids as $personalityId) {
                        PetPersonalityTag::create([
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'all_tag_id' => $personalityId
                        ]);
                    }
                }

                // Attach profile pictures if provided
                if ($request->has('profile_picture_ids')) {
                    foreach ($request->profile_picture_ids as $attachmentId) {
                        PetProfilePicture::create([
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'attachment_id' => $attachmentId
                        ]);
                    }
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
