<?php

namespace App\Http\Controllers;

use App\Http\Requests\PetRequest;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
                $availableStatus = DB::table('mt_all_status')
                    ->where('status_name', 'available')
                    ->firstOrFail();
                
                $petData['status_id'] = $availableStatus->id;
                
                $pet = Pet::create($petData);

                // Attach physique tags if provided
                if ($request->has('physique_ids')) {
                    $this->attachMany($pet, $request->physique_ids, 'tr_all_tag_pet_physique_record', 'all_tag_id');
                }

                // Attach personality tags if provided
                if ($request->has('personality_ids')) {
                    $this->attachMany($pet, $request->personality_ids, 'tr_all_tag_pet_personality_record', 'all_tag_id');
                }

                // Attach profile pictures if provided
                if ($request->has('profile_picture_ids')) {
                    $this->attachMany($pet, $request->profile_picture_ids, 'tr_pet_profile_picture', 'attachment_id');
                }

                return $pet;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Pet created successfully',
                'data' => $pet
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Pet creation failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create pet',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating the pet.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Helper method to attach multiple records to a pet.
     */
    private function attachMany(Pet $pet, array $ids, string $tableName, string $foreignKeyName): void
    {
        if (empty($ids)) {
            return;
        }

        $records = array_map(function ($id) use ($pet, $foreignKeyName) {
            return [
                'id' => Str::uuid(),
                'pet_id' => $pet->id,
                $foreignKeyName => $id,
            ];
        }, $ids);

        DB::table($tableName)->insert($records);
    }
}
