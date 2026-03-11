<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\RefreshToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permission:cache-reset');

        // Include UUID generation explicitly for test environment stability
        Role::firstOrCreate(['name' => RoleEnum::ADOPTER->value, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);
        Role::firstOrCreate(['name' => RoleEnum::PROVIDER->value, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);
    }

    public function test_register_requires_valid_data()
    {
        $payload = [
            'email' => 'invalid-email',
            'role' => 'invalid-role',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_user_can_register()
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => RoleEnum::ADOPTER->value,
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'error' => false,
                'status' => 'success',
                'message' => 'User registered successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'username', 'email', 'roles'],
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'refresh_expires_in',
                ],
            ]);

        $this->assertDatabaseHas('mt_user', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->assertDatabaseHas('tr_user_activation', [
            'user_id' => $response->json('data.user.id'),
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(RoleEnum::ADOPTER->value));
    }

    public function test_user_cannot_register_with_existing_email()
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $payload = [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => RoleEnum::ADOPTER->value,
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validation_fails_on_empty_data()
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole(RoleEnum::ADOPTER->value);

        $payload = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'refresh_expires_in',
                ],
            ]);
    }

    public function test_inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $payload = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'error' => true,
                'status' => 'error',
                'message' => 'Wrong credentials or account inactive',
            ]);
    }

    public function test_refresh_token_validation_fails()
    {
        $response = $this->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create(['is_active' => true]);
        auth('api')->login($user);

        $refreshToken = RefreshToken::createToken($user->id, request()->ip(), request()->userAgent());

        $oldHashedToken = hash('sha256', $refreshToken);

        $this->assertDatabaseHas('mt_refresh_token', [
            'token' => $oldHashedToken,
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
                'message' => 'Token refreshed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'refresh_expires_in',
                ],
            ]);

        $this->assertDatabaseMissing('mt_refresh_token', [
            'token' => $oldHashedToken,
        ]);

        $newRefreshTokenString = $response->json('data.refresh_token');
        $this->assertNotNull($newRefreshTokenString);

        $newHashedToken = hash('sha256', $newRefreshTokenString);

        $this->assertDatabaseHas('mt_refresh_token', [
            'token' => $newHashedToken,
            'user_id' => $user->id,
            'used_at' => null,
        ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $token = auth('api')->login($user);

        $refreshToken = RefreshToken::createToken($user->id, request()->ip(), request()->userAgent());

        $this->assertDatabaseHas('mt_refresh_token', [
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
                'message' => 'Successfully logged out from all devices',
            ]);

        $this->assertEquals(2, $user->fresh()->token_version);

        $this->assertDatabaseMissing('mt_refresh_token', [
            'user_id' => $user->id,
        ]);

        $invalidResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/logout');

        $invalidResponse->assertStatus(401);
    }
}
