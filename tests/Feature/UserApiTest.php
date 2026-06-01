<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permission:cache-reset');

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate([
            'name' => RoleEnum::ADMIN->value,
            'guard_name' => 'api',
        ], ['id' => (string) Str::uuid()]);

        Role::firstOrCreate([
            'name' => RoleEnum::ADOPTER->value,
            'guard_name' => 'api',
        ], ['id' => (string) Str::uuid()]);

        \App\Models\Status::firstOrCreate([
            'name' => \App\Enums\ReportStatusEnum::RESOLVED->value,
            'type' => \App\Enums\StatusTypeEnum::REPORT->value,
        ], ['id' => (string) Str::uuid()]);
    }

    public function test_get_users_list()
    {
        User::factory()->count(2)->create(['is_active' => true, 'token_version' => 1]);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_get_user_details()
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);

        $response = $this->getJson("/api/v1/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ])
            ->assertJsonFragment([
                'id' => $user->id,
            ]);
    }

    public function test_update_profile()
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $token = auth('api')->login($user);

        $payload = [
            'name' => 'Updated Name',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->putJson('/api/v1/profile', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
            ]);
    }

    public function test_delete_profile()
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('password12'),
            'token_version' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->deleteJson('/api/v1/profile', [
            'password' => 'password12',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('mt_user', ['id' => $user->id]);
    }

    public function test_deactivate_profile()
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('password12'),
            'token_version' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->patchJson('/api/v1/profile/deactivate', [
            'password' => 'password12',
        ]);

        $response->assertStatus(200);
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_deactivate_profile_wrong_password()
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('password12'),
            'token_version' => 1,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->patchJson('/api/v1/profile/deactivate', [
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_deactivate_user()
    {
        $admin = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $admin->assignRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/v1/users/{$user->getRouteKey()}/deactivate", [
            'notes' => 'Spam',
        ]);

        $response->assertStatus(200);
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_admin_activate_user()
    {
        $admin = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $admin->assignRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $user = User::factory()->create(['is_active' => false, 'token_version' => 1]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/v1/users/{$user->getRouteKey()}/activate", [
            'notes' => 'Clear',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_get_user_channels()
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/users/channels');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);
    }
}
