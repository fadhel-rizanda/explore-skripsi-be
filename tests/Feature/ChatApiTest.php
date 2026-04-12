<?php

namespace Tests\Feature;

use App\Enums\ChatTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatApiTest extends TestCase
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

    public function test_user_can_get_chat_rooms()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'type' => ChatTypeEnum::PRIVATE->value,
            'created_by' => $user->id,
        ]);
        $chat->users()->attach([$user->id, $user2->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/chats');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_private_chat()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $payload = [
            'name' => 'Test Group',
            'type' => ChatTypeEnum::PRIVATE->value,
            'user_ids' => [$user2->id],
            'is_create_manually' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/chats', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.type', ChatTypeEnum::PRIVATE->value);

        $this->assertDatabaseHas('tr_chat_room', ['user_id' => $user->id]);
        $this->assertDatabaseHas('tr_chat_room', ['user_id' => $user2->id]);
    }

    public function test_user_can_create_public_chat()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $user3 = $this->createUser();
        $token = auth('api')->login($user);

        $payload = [
            'name' => 'Test Group',
            'type' => ChatTypeEnum::PUBLIC->value,
            'user_ids' => [$user2->id, $user3->id],
            'is_create_manually' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/chats', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Test Group');
    }

    public function test_user_can_send_message()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'type' => ChatTypeEnum::PRIVATE->value,
            'created_by' => $user->id,
        ]);
        $chat->users()->attach([$user->id, $user2->id]);

        $payload = [
            'content' => 'Hello there!',
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/chats/{$chat->id}/messages", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.content', 'Hello there!');

        $this->assertDatabaseHas('tr_message', ['content' => 'Hello there!', 'chat_id' => $chat->id]);
    }

    public function test_user_can_get_messages()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'type' => ChatTypeEnum::PRIVATE->value,
            'created_by' => $user->id,
        ]);
        $chat->users()->attach([$user->id, $user2->id]);

        Message::create([
            'id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'content' => 'Test Message',
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson("/api/v1/chats/{$chat->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.messages');
    }

    public function test_user_can_mark_room_as_read()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'type' => ChatTypeEnum::PRIVATE->value,
            'created_by' => $user->id,
        ]);
        $chat->users()->attach([$user->id, $user2->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->patchJson("/api/v1/chats/{$chat->id}/read");

        $response->assertStatus(200);
        $this->assertNotNull($chat->users()->find($user->id)->pivot->last_read_at);
    }

    public function test_creator_can_delete_chat()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'type' => ChatTypeEnum::PRIVATE->value,
            'created_by' => $user->id,
        ]);
        $chat->users()->attach([$user->id, $user2->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(200);
        //        $this->assertDatabaseMissing('mt_chat', ['id' => $chat->id]);
    }

    public function test_user_can_leave_chat()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'type' => ChatTypeEnum::PRIVATE->value,
            'created_by' => $user2->id,
        ]);
        $chat->users()->attach([$user->id, $user2->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/chats/{$chat->id}/leave");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tr_chat_room', ['chat_id' => $chat->id, 'user_id' => $user->id]);
    }

    public function test_creator_can_remove_user_from_chat()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'type' => ChatTypeEnum::PUBLIC->value,
            'name' => 'Group',
            'created_by' => $user->id,
        ]);
        $chat->users()->attach([$user->id, $user2->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/chats/{$chat->id}/members/{$user2->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tr_chat_room', ['chat_id' => $chat->id, 'user_id' => $user2->id]);
    }

    public function test_user_can_delete_own_message()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $token = auth('api')->login($user);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'type' => ChatTypeEnum::PRIVATE->value,
            'created_by' => $user->id,
        ]);
        $chat->users()->attach([$user->id, $user2->id]);

        $message = Message::create([
            'id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'content' => 'Delete me',
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/chats/{$chat->id}/messages/{$message->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tr_message', ['id' => $message->id]);
    }
}
