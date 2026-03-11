<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\AllTag;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GeneralApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Cache::flush();
        $this->artisan('permission:cache-reset');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Setup Roles
        Role::firstOrCreate(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);
    }

    public function test_list_statuses()
    {
        Status::create(['id' => (string) Str::uuid(), 'name' => 'Test Status', 'type' => 'test']);

        $response = $this->getJson('/api/v1/general/statuses');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_list_tags()
    {
        AllTag::create(['id' => (string) Str::uuid(), 'name' => 'Test Tag', 'type' => 'test']);

        $response = $this->getJson('/api/v1/general/tags');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_list_roles()
    {
        $response = $this->getJson('/api/v1/general/roles');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    public function test_list_provinces()
    {
        Province::create(['id' => '1', 'name' => 'Test Province']);

        $response = $this->getJson('/api/v1/general/provinces');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_list_regencies()
    {
        Province::create(['id' => '1', 'name' => 'Test Province']);
        Regency::create(['id' => '11', 'province_id' => '1', 'name' => 'Test Regency']);

        $response = $this->getJson('/api/v1/general/provinces/1/regencies');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_list_districts()
    {
        // Must exist or foreign key constraint fails if Regency create checks it (it doesn't in this simplified setup, but let's be safe)
        Province::create(['id' => '1', 'name' => 'Test Province']);
        Regency::create(['id' => '11', 'province_id' => '1', 'name' => 'Test Regency']);
        District::create(['id' => '111', 'regency_id' => '11', 'name' => 'Test District']);

        $response = $this->getJson('/api/v1/general/regencies/11/districts');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_list_users_options()
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $token = auth('api')->login($user);

        User::factory()->count(2)->create(['is_active' => true]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/general/users');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }
}
