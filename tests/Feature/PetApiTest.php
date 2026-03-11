<?php

namespace Tests\Feature;

use App\Enums\PetStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Models\AllTag;
use App\Models\Pet;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permission:cache-reset');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Setup Roles
        $roles = [RoleEnum::ADMIN->value, RoleEnum::PROVIDER->value, RoleEnum::ADOPTER->value];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);
        }

        // Setup Statuses
        Status::firstOrCreate([
            'name' => PetStatusEnum::AVAILABLE->value,
            'type' => StatusTypeEnum::PET->value,
        ], ['id' => (string) Str::uuid()]);

        Status::firstOrCreate([
            'name' => \App\Enums\ReportStatusEnum::RESOLVED->value,
            'type' => StatusTypeEnum::REPORT->value,
        ], ['id' => (string) Str::uuid()]);
    }

    private function createTag($type, $name)
    {
        return AllTag::firstOrCreate([
            'name' => $name,
            'type' => $type,
        ], ['id' => (string) Str::uuid()]);
    }

    private function createUserWithRole($role)
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $user->assignRole($role);

        return $user;
    }

    private function createPet(User $user)
    {
        $typeTag = $this->createTag('TYPE_OF_ANIMAL', 'Cat');
        $status = Status::where('name', PetStatusEnum::AVAILABLE->value)->first();

        return Pet::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'status_id' => $status->id,
            'type_of_animal_id' => $typeTag->id,
            'name' => 'Garfield',
            'date_of_birth' => now()->subYear(),
            'gender' => 'male',
            'size' => 'medium',
            'about' => 'Lazy cat',
            'breed' => 'Persian',
            'special_needs' => false,
            'is_active' => true,
        ]);
    }

    public function test_get_pets_list()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $this->createPet($user);

        $response = $this->getJson('/api/v1/pets');

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_get_pet_details()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $pet = $this->createPet($user);

        $response = $this->getJson("/api/v1/pets/{$pet->getRouteKey()}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }
}
