<?php

namespace Tests\Feature;

use App\Enums\ModelReferenceEnum;
use App\Enums\ReportStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Models\Post;
use App\Models\Report;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportApiTest extends TestCase
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

        // Setup Statuses
        Status::firstOrCreate([
            'name' => ReportStatusEnum::ACTIVE->value,
            'type' => StatusTypeEnum::REPORT->value,
        ], ['id' => (string) Str::uuid()]);

        Status::firstOrCreate([
            'name' => ReportStatusEnum::RESOLVED->value,
            'type' => StatusTypeEnum::REPORT->value,
        ], ['id' => (string) Str::uuid()]);
    }

    private function createUserWithRole($role)
    {
        $user = User::factory()->create(['is_active' => true, 'token_version' => 1]);
        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function createPost(User $creator)
    {
        return Post::create([
            'id' => (string) Str::uuid(),
            'title' => 'Post to report',
            'content' => 'Some content',
            'created_by' => $creator->id,
            'is_active' => true,
        ]);
    }

    public function test_create_report()
    {
        $user = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $post = $this->createPost($creator);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/reports', [
                'reference_type' => ModelReferenceEnum::POST->value,
                'reference_id' => $post->id,
                'notes' => 'This post is spammy.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertDatabaseHas(Report::TABLE, [
            'reference_id' => $post->id,
            'created_by' => $user->id,
        ]);
    }

    public function test_list_reports_as_admin()
    {
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        // Create a report first
        $user = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $post = $this->createPost($user);
        $status = Status::where('name', ReportStatusEnum::ACTIVE->value)->first();
        Report::create([
            'id' => (string) Str::uuid(),
            'reference_type' => ModelReferenceEnum::POST->value,
            'reference_id' => $post->id,
            'notes' => 'Initial report',
            'status_id' => $status->id,
            'created_by' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/reports');

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_report_detail_as_admin()
    {
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $user = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $post = $this->createPost($user);
        $status = Status::where('name', ReportStatusEnum::ACTIVE->value)->first();
        $report = Report::create([
            'id' => (string) Str::uuid(),
            'reference_type' => ModelReferenceEnum::POST->value,
            'reference_id' => $post->id,
            'notes' => 'Detailed report',
            'status_id' => $status->id,
            'created_by' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_update_report_status_as_admin()
    {
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $user = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $post = $this->createPost($user);
        $activeStatus = Status::where('name', ReportStatusEnum::ACTIVE->value)->first();
        $resolvedStatus = Status::where('name', ReportStatusEnum::RESOLVED->value)->first();

        $report = Report::create([
            'id' => (string) Str::uuid(),
            'reference_type' => ModelReferenceEnum::POST->value,
            'reference_id' => $post->id,
            'notes' => 'Report to resolve',
            'status_id' => $activeStatus->id,
            'created_by' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->putJson("/api/v1/reports/{$report->id}/status/{$resolvedStatus->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertEquals($resolvedStatus->id, $report->fresh()->status_id);
    }

    public function test_delete_report_as_admin()
    {
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $user = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $post = $this->createPost($user);
        $status = Status::where('name', ReportStatusEnum::ACTIVE->value)->first();
        $report = Report::create([
            'id' => (string) Str::uuid(),
            'reference_type' => ModelReferenceEnum::POST->value,
            'reference_id' => $post->id,
            'notes' => 'Report to delete',
            'status_id' => $status->id,
            'created_by' => $user->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->deleteJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing(Report::TABLE, [
            'id' => $report->id,
        ]);
    }

    public function test_non_admin_cannot_list_reports()
    {
        $user = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/reports');

        $response->assertStatus(404);
    }
}
