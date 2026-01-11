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
                    ->first();
                
                if ($availableStatus) {
                    $petData['status_id'] = $availableStatus->id;
                }
                
                $pet = Pet::create($petData);

                // Attach physique tags if provided
                if ($request->has('physique_ids')) {
                    foreach ($request->physique_ids as $physiqueId) {
                        DB::table('tr_all_tag_pet_physique_record')->insert([
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'all_tag_id' => $physiqueId
                        ]);
                    }
                }

                // Attach personality tags if provided
                if ($request->has('personality_ids')) {
                    foreach ($request->personality_ids as $personalityId) {
                        DB::table('tr_all_tag_pet_personality_record')->insert([
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'all_tag_id' => $personalityId
                        ]);
                    }
                }

                return $pet;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Pet created successfully',
                'data' => $pet
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create pet',
                'error' => $e->getMessage()
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
}
