<?php

namespace Tests\Feature;

use App\Enums\ReportStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permission:cache-reset');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [RoleEnum::ADMIN->value, RoleEnum::PROVIDER->value, RoleEnum::ADOPTER->value];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);
        }

        Status::firstOrCreate([
            'name' => ReportStatusEnum::RESOLVED->value,
            'type' => StatusTypeEnum::REPORT->value,
        ], ['id' => (string) Str::uuid()]);

        // Setup common entities
        \App\Models\Province::firstOrCreate(['id' => '11'], ['name' => 'Test Province']);
        \App\Models\Regency::firstOrCreate(['id' => '1101', 'province_id' => '11'], ['name' => 'Test Regency']);
        \App\Models\District::firstOrCreate(['id' => '1101010', 'regency_id' => '1101'], ['name' => 'Test District']);
    }

    private function createUserWithRole($role)
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function createPost(User $creator, ?Community $community = null)
    {
        return Post::create([
            'id' => (string) Str::uuid(),
            'title' => 'Test Post Title',
            'content' => 'Test Post Content',
            'community_id' => $community?->id,
            'created_by' => $creator->id,
            'is_active' => true,
        ]);
    }

    private function createComment(User $creator, Post $post, ?Comment $parent = null)
    {
        return Comment::create([
            'id' => (string) Str::uuid(),
            'content' => 'Test Comment Content',
            'post_id' => $post->id,
            'created_by' => $creator->id,
            'parent_id' => $parent?->id,
        ]);
    }

    public function test_get_posts_list()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $this->createPost($user);

        $response = $this->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_get_post_details()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($user);

        $response = $this->getJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_create_post()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/posts', [
                'title' => 'My New Post',
                'content' => 'Content of my new post',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertDatabaseHas(Post::TABLE, [
            'title' => 'My New Post',
            'created_by' => $user->id,
        ]);
    }

    public function test_update_post()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($user);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->putJson("/api/v1/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertDatabaseHas(Post::TABLE, [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_delete_post()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($user);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertDatabaseMissing(Post::TABLE, [
            'id' => $post->id,
        ]);
    }

    public function test_like_post()
    {
        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($creator);

        $liker = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $token = auth('api')->login($liker);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/posts/{$post->id}/likes");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
                'message' => 'Post liked successfully.',
            ]);

        $this->assertTrue($post->likes()->where('user_id', $liker->id)->exists());

        // Toggle unlike
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/posts/{$post->id}/likes");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Post unliked successfully.']);

        $this->assertFalse($post->likes()->where('user_id', $liker->id)->exists());
    }

    public function test_takedown_post()
    {
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($creator);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/posts/{$post->id}/takedown", [
                'notes' => 'Inappropriate content',
            ]);

        $response->assertStatus(200);
        $this->assertFalse($post->fresh()->is_active);
    }

    public function test_restore_post()
    {
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($creator);
        $post->update(['is_active' => false]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/posts/{$post->id}/restore", [
                'notes' => 'Content cleared',
            ]);

        $response->assertStatus(200);
        $this->assertTrue($post->fresh()->is_active);
    }

    public function test_get_comments_list()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($user);
        $this->createComment($user, $post);

        $response = $this->getJson("/api/v1/posts/{$post->id}/comments");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_get_replies_list()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($user);
        $comment = $this->createComment($user, $post);
        $this->createComment($user, $post, $comment);

        $response = $this->getJson("/api/v1/posts/{$post->id}/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_create_comment()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($user);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'content' => 'New comment',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas(Comment::TABLE, [
            'content' => 'New comment',
            'post_id' => $post->id,
        ]);
    }

    public function test_create_reply()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($user);
        $comment = $this->createComment($user, $post);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'content' => 'New reply',
                'parent_id' => $comment->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas(Comment::TABLE, [
            'content' => 'New reply',
            'parent_id' => $comment->id,
        ]);
    }

    public function test_delete_comment()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($user);
        $comment = $this->createComment($user, $post);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/posts/{$post->id}/comments/{$comment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing(Comment::TABLE, [
            'id' => $comment->id,
        ]);
    }
}
