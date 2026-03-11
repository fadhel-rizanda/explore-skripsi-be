<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Cache::flush();
        $this->artisan('permission:cache-reset');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Setup Roles
        $roles = [RoleEnum::ADMIN->value, RoleEnum::PROVIDER->value, RoleEnum::ADOPTER->value];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);
        }
    }

    private function createUser()
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $user->assignRole(RoleEnum::ADOPTER->value);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_user_can_get_notifications()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        Notification::create([
            'id' => (string) Str::uuid(),
            'title' => 'Test Notification',
            'message' => 'Hello World',
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_get_unread_notifications_only()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        Notification::create([
            'id' => (string) Str::uuid(),
            'title' => 'Read Notification',
            'message' => 'Hello',
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        Notification::create([
            'id' => (string) Str::uuid(),
            'title' => 'Unread Notification',
            'message' => 'World',
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/notifications?unread_only=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_mark_notification_as_read()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'title' => 'Test',
            'message' => 'Test',
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/notifications/{$notification->id}/mark-as-read");

        $response->assertStatus(200);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_notification_as_unread()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'title' => 'Test',
            'message' => 'Test',
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/notifications/{$notification->id}/mark-as-unread");

        $response->assertStatus(200);
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        Notification::create([
            'id' => (string) Str::uuid(),
            'title' => '1',
            'message' => '1',
            'user_id' => $user->id,
        ]);

        Notification::create([
            'id' => (string) Str::uuid(),
            'title' => '2',
            'message' => '2',
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/notifications/mark-all-as-read');

        $response->assertStatus(200);
        $this->assertEquals(0, Notification::where('user_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_user_can_mark_all_notifications_as_unread()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        Notification::create([
            'id' => (string) Str::uuid(),
            'title' => '1',
            'message' => '1',
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/notifications/mark-all-as-unread');

        $response->assertStatus(200);
        $this->assertEquals(1, Notification::where('user_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_user_can_delete_notification()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'title' => 'Delete Me',
            'message' => 'Bye',
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tr_notification', ['id' => $notification->id]);
    }
}
