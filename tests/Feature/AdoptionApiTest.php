<?php

namespace Tests\Feature;

use App\Enums\AdoptionStageEnum;
use App\Enums\AdoptionStatusEnum;
use App\Enums\PetStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Enums\TagTypeEnum;
use App\Models\Adoption;
use App\Models\AllTag;
use App\Models\Attachment;
use App\Models\District;
use App\Models\Handover;
use App\Models\Pet;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdoptionApiTest extends TestCase
{
    use RefreshDatabase;

    protected $province;

    protected $regency;

    protected $district;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Cache::flush();
        $this->artisan('permission:cache-reset');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [RoleEnum::ADMIN->value, RoleEnum::PROVIDER->value, RoleEnum::ADOPTER->value];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);
        }

        $this->province = Province::create(['id' => '1', 'name' => 'Test Province']);
        $this->regency = Regency::create(['id' => '1', 'province_id' => '1', 'name' => 'Test Regency']);
        $this->district = District::create(['id' => '1', 'regency_id' => '1', 'name' => 'Test District']);

        foreach (PetStatusEnum::cases() as $case) {
            Status::create(['id' => (string) Str::uuid(), 'name' => $case->value, 'type' => StatusTypeEnum::PET->value]);
        }

        foreach (AdoptionStatusEnum::cases() as $case) {
            Status::create(['id' => (string) Str::uuid(), 'name' => $case->value, 'type' => StatusTypeEnum::ADOPTION->value]);
        }

        foreach (AdoptionStageEnum::cases() as $case) {
            AllTag::create(['id' => (string) Str::uuid(), 'name' => $case->value, 'type' => TagTypeEnum::ADOPTION_STAGE->value]);
        }

        AllTag::create(['id' => (string) Str::uuid(), 'name' => 'Identity', 'type' => TagTypeEnum::REQUIREMENT->value]);
    }

    private function createUserWithRole($role)
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function createAttachment(User $user)
    {
        return Attachment::create([
            'id' => (string) Str::uuid(),
            'uploaded_by' => $user->id,
            'filename' => 'test_image.jpg',
            'path' => 'attachments/test_image.jpg',
            'public_url' => 'http://localhost/test_image.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);
    }

    public function test_full_adoption_lifecycle()
    {
        $adopter = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $provider = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);

        $animalType = AllTag::create(['id' => (string) Str::uuid(), 'name' => 'Dog', 'type' => TagTypeEnum::TYPE_OF_ANIMAL->value]);
        $pet = Pet::create([
            'id' => (string) Str::uuid(),
            'name' => 'Lucky',
            'user_id' => $provider->id,
            'status_id' => Status::where('name', PetStatusEnum::AVAILABLE->value)->where('type', StatusTypeEnum::PET->value)->first()->id,
            'type_of_animal_id' => $animalType->id,
            'size' => 'Medium',
            'date_of_birth' => '2023-01-01',
            'gender' => 'Male',
            'about' => 'A friendly dog.',
            'breed' => 'Mixed',
            'is_active' => true,
        ]);

        // 2. Adopter applies
        $adopterToken = auth('api')->login($adopter);
        $response = $this->withHeaders(['Authorization' => "Bearer {$adopterToken}"])
            ->postJson("/api/v1/pets/{$pet->id}/adopt");

        $response->assertStatus(200);
        $adoptionId = $response->json('data.id');
        $this->assertDatabaseHas(Adoption::TABLE, ['id' => $adoptionId, 'adopter_id' => $adopter->id]);

        // 3. Provider schedules Meet-N-Greet
        $providerToken = auth('api')->login($provider);
        $response = $this->withHeaders(['Authorization' => "Bearer {$providerToken}"])
            ->postJson("/api/v1/adoptions/{$adoptionId}/meet-n-greet", [
                'scheduled_time' => now()->addDays(1)->toDateTimeString(),
                'address' => [
                    'street' => 'Test Street',
                    'province_id' => $this->province->id,
                    'regency_id' => $this->regency->id,
                    'district_id' => $this->district->id,
                ],
            ]);

        $response->assertStatus(200);
        $meetNGreetId = $response->json('data.id');

        // 4. Adopter approves Meet-N-Greet
        $adopterToken = auth('api')->login($adopter);
        $response = $this->withHeaders(['Authorization' => "Bearer {$adopterToken}"])
            ->patchJson("/api/v1/adoptions/{$adoptionId}/meet-n-greet/{$meetNGreetId}/approve");
        $response->assertStatus(200);

        // 5. Provider finalizes Meet-N-Greet
        $providerToken = auth('api')->login($provider);
        $response = $this->withHeaders(['Authorization' => "Bearer {$providerToken}"])
            ->patchJson("/api/v1/adoptions/{$adoptionId}/meet-n-greet/{$meetNGreetId}/finalize");
        $response->assertStatus(200);

        // 6. Provider sets requirements
        $requirementTag = AllTag::where('type', TagTypeEnum::REQUIREMENT->value)->first();
        $providerToken = auth('api')->login($provider);
        $response = $this->withHeaders(['Authorization' => "Bearer {$providerToken}"])
            ->postJson("/api/v1/adoptions/{$adoptionId}/requirements", [
                'requirements' => [
                    [
                        'name' => 'KTP',
                        'notes' => 'Please upload your ID',
                        'tag_id' => $requirementTag->id,
                    ],
                ],
            ]);
        $response->assertStatus(200);
        $requirementId = Requirement::where('adoption_id', $adoptionId)->first()->id;

        // 7. Adopter fills requirement
        $adopterToken = auth('api')->login($adopter);
        $attachment = $this->createAttachment($adopter);
        $response = $this->withHeaders(['Authorization' => "Bearer {$adopterToken}"])
            ->postJson("/api/v1/adoptions/{$adoptionId}/requirements/{$requirementId}/fill", [
                'attachment_id' => $attachment->id,
            ]);
        $response->assertStatus(200);

        // 8. Provider approves requirement
        $providerToken = auth('api')->login($provider);
        $response = $this->withHeaders(['Authorization' => "Bearer {$providerToken}"])
            ->patchJson("/api/v1/adoptions/{$adoptionId}/requirements/{$requirementId}/approve");
        $response->assertStatus(200);

        // 9. Provider finalizes requirements
        $providerToken = auth('api')->login($provider);
        $response = $this->withHeaders(['Authorization' => "Bearer {$providerToken}"])
            ->patchJson("/api/v1/adoptions/{$adoptionId}/requirements/finalize");
        $response->assertStatus(200);

        // 10. Provider schedules Handover
        $providerToken = auth('api')->login($provider);
        $response = $this->withHeaders(['Authorization' => "Bearer {$providerToken}"])
            ->postJson("/api/v1/adoptions/{$adoptionId}/handover/meet-n-greet", [
                'scheduled_time' => now()->addDays(2)->toDateTimeString(),
                'address' => [
                    'street' => 'Handover Street',
                    'province_id' => $this->province->id,
                    'regency_id' => $this->regency->id,
                    'district_id' => $this->district->id,
                ],
            ]);
        $response->assertStatus(200);
        $handoverId = Handover::where('adoption_id', $adoptionId)->first()->id;

        // 11. Adopter approves handover schedule
        $adopterToken = auth('api')->login($adopter);
        $response = $this->withHeaders(['Authorization' => "Bearer {$adopterToken}"])
            ->patchJson("/api/v1/adoptions/{$adoptionId}/handover/{$handoverId}/meet-n-greet/approve");
        $response->assertStatus(200);

        // 12. Both set handover evidence
        $adopterToken = auth('api')->login($adopter);
        $adopterAttachment = $this->createAttachment($adopter);
        $this->withHeaders(['Authorization' => "Bearer {$adopterToken}"])
            ->postJson("/api/v1/adoptions/{$adoptionId}/handover/{$handoverId}/evidence", [
                'attachment_ids' => [$adopterAttachment->id],
            ])->assertStatus(200);

        $providerToken = auth('api')->login($provider);
        $providerAttachment = $this->createAttachment($provider);
        $this->withHeaders(['Authorization' => "Bearer {$providerToken}"])
            ->postJson("/api/v1/adoptions/{$adoptionId}/handover/{$handoverId}/evidence", [
                'attachment_ids' => [$providerAttachment->id],
            ])->assertStatus(200);

        // 13. Finalize Handover
        $adopterToken = auth('api')->login($adopter);
        $this->withHeaders(['Authorization' => "Bearer {$adopterToken}"])
            ->patchJson("/api/v1/adoptions/{$adoptionId}/handover/{$handoverId}/finalize")
            ->assertStatus(200);

        $providerToken = auth('api')->login($provider);
        $this->withHeaders(['Authorization' => "Bearer {$providerToken}"])
            ->patchJson("/api/v1/adoptions/{$adoptionId}/handover/{$handoverId}/finalize")
            ->assertStatus(200);

        $adminToken = auth('api')->login($admin);
        $this->withHeaders(['Authorization' => "Bearer {$adminToken}"])
            ->patchJson("/api/v1/adoptions/{$adoptionId}/handover/{$handoverId}/finalize")
            ->assertStatus(200);

        // 14. Verify Completion
        $this->assertEquals(PetStatusEnum::ADOPTED->value, $pet->fresh()->status->name);
        $this->assertEquals(AdoptionStatusEnum::COMPLETED->value, Adoption::find($adoptionId)->status->name);
    }
}
