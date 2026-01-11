<?php

namespace App\Http\Controllers;

use App\Http\Requests\PetRequest;
use App\Models\Pet;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PetController extends Controller
{
    use ResponseAPI;
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
                    $physiqueRecords = array_map(function ($physiqueId) use ($pet) {
                        return [
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'all_tag_id' => $physiqueId
                        ];
                    }, $request->physique_ids);
                    
                    DB::table('tr_all_tag_pet_physique_record')->insert($physiqueRecords);
                }

                // Attach personality tags if provided
                if ($request->has('personality_ids')) {
                    $personalityRecords = array_map(function ($personalityId) use ($pet) {
                        return [
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'all_tag_id' => $personalityId
                        ];
                    }, $request->personality_ids);
                    
                    DB::table('tr_all_tag_pet_personality_record')->insert($personalityRecords);
                }

                // Attach profile pictures if provided
                if ($request->has('profile_picture_ids')) {
                    $profilePictureRecords = array_map(function ($attachmentId) use ($pet) {
                        return [
                            'id' => Str::uuid(),
                            'pet_id' => $pet->id,
                            'attachment_id' => $attachmentId
                        ];
                    }, $request->profile_picture_ids);
                    
                    DB::table('tr_pet_profile_picture')->insert($profilePictureRecords);
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
