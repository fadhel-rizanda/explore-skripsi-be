<?php

namespace Tests\Feature;

use App\Enums\AttachmentTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Attachment;
use App\Models\Role;
use App\Models\User;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AttachmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected $s3Mock;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Cache::flush();
        $this->artisan('permission:cache-reset');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Setup Roles
        Role::firstOrCreate(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);
        Role::firstOrCreate(['name' => RoleEnum::ADOPTER->value, 'guard_name' => 'api'], ['id' => (string) Str::uuid()]);

        // Mock S3Client
        $this->s3Mock = Mockery::mock(S3Client::class);
        $this->app->instance(S3Client::class, $this->s3Mock);

        Storage::fake('s3');
    }

    private function createUser()
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $user->assignRole(RoleEnum::ADOPTER->value);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_user_can_generate_presigned_url()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        $this->s3Mock->shouldReceive('getCommand')
            ->once()
            ->andReturn(new \Aws\Command('PutObject', []));

        $mockRequest = new Psr7Request('PUT', new Uri('https://example.com/upload'));
        $this->s3Mock->shouldReceive('createPresignedRequest')
            ->once()
            ->andReturn($mockRequest);

        $payload = [
            'filename' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'is_public' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/attachments/presigned-url', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('mt_attachment', [
            'filename' => 'test.jpg',
            'status' => AttachmentTypeEnum::PENDING->value,
        ]);
    }

    public function test_user_can_confirm_upload()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        $attachment = Attachment::create([
            'id' => (string) Str::uuid(),
            'filename' => 'test.jpg',
            'path' => 'public/test.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => AttachmentTypeEnum::PENDING->value,
            'uploaded_by' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->patchJson("/api/v1/attachments/{$attachment->id}/confirm");

        $response->assertStatus(200);
        $this->assertEquals(AttachmentTypeEnum::COMPLETED->value, $attachment->fresh()->status);
    }

    public function test_user_can_generate_download_url()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        $attachment = Attachment::create([
            'id' => (string) Str::uuid(),
            'filename' => 'test.jpg',
            'path' => 'private/test.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => AttachmentTypeEnum::COMPLETED->value,
            'uploaded_by' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson("/api/v1/attachments/{$attachment->id}/download-url");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['download_url']]);
    }

    public function test_user_can_delete_attachment()
    {
        $user = $this->createUser();
        $token = auth('api')->login($user);

        $attachment = Attachment::create([
            'id' => (string) Str::uuid(),
            'filename' => 'test.jpg',
            'path' => 'public/test.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => AttachmentTypeEnum::COMPLETED->value,
            'uploaded_by' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/attachments/{$attachment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('mt_attachment', ['id' => $attachment->id]);
    }
}
