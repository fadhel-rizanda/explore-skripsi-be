<?php

namespace Tests\Feature;

use App\Enums\ReportStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Models\Address;
use App\Models\AllTag;
use App\Models\Community;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommunityApiTest extends TestCase
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

        \App\Models\Province::firstOrCreate(['id' => '11'], ['name' => 'Test Province']);
        \App\Models\Regency::firstOrCreate(['id' => '1101', 'province_id' => '11'], ['name' => 'Test Regency']);
        \App\Models\District::firstOrCreate(['id' => '1101010', 'regency_id' => '1101'], ['name' => 'Test District']);
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
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function createAddress()
    {
        return Address::create([
            'street' => '123 Fake Street',
            'province_id' => '11',
            'regency_id' => '1101',
            'district_id' => '1101010',
            'zip_code' => '12345',
        ]);
    }

    private function createCommunity(User $creator)
    {
        $address = $this->createAddress();

        $community = Community::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Community ' . Str::random(5),
            'description' => 'Test Description',
            'address_id' => $address->id,
            'created_by' => $creator->id,
            'is_active' => true,
        ]);

        $tag = $this->createTag('COMMUNITY_TOPIC', 'Testing');
        $community->tags()->attach($tag->id);

        $community->members()->attach($creator->id);

        return $community;
    }

    public function test_get_communities_list()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $this->createCommunity($user);

        $response = $this->getJson('/api/v1/communities');

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_get_community_details()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $community = $this->createCommunity($user);

        $response = $this->getJson("/api/v1/communities/{$community->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);
    }

    public function test_create_community()
    {
        $user = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $tag = $this->createTag('COMMUNITY_TOPIC', 'Testing');
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])->postJson('/api/v1/communities', [
            'name' => 'New Awesome Community',
            'description' => 'We do testing',
            'use_owner_address' => false,
            'address' => [
                'street' => '456 Test Ave',
                'province_id' => '11',
                'regency_id' => '1101',
                'district_id' => '1101010',
                'zip_code' => '67890',
            ],
            'tag_ids' => [$tag->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertDatabaseHas(Community::TABLE, [
            'name' => 'New Awesome Community',
            'created_by' => $user->id,
        ]);
    }

    public function test_follow_community()
    {
        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $community = $this->createCommunity($creator);

        $follower = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $token = auth('api')->login($follower);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])->postJson("/api/v1/communities/{$community->id}/follow");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
                'message' => 'Community followed successfully.',
            ]);

        $this->assertTrue($community->members()->where('user_id', $follower->id)->exists());
    }

    public function test_unfollow_community()
    {
        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $community = $this->createCommunity($creator);

        $follower = $this->createUserWithRole(RoleEnum::ADOPTER->value);
        $community->members()->attach($follower->id); // Follow initially
        $token = auth('api')->login($follower);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])->postJson("/api/v1/communities/{$community->id}/follow");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
                'message' => 'Community unfollowed successfully.',
            ]);

        $this->assertFalse($community->members()->where('user_id', $follower->id)->exists());
    }

    public function test_update_community()
    {
        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $community = $this->createCommunity($creator);
        $token = auth('api')->login($creator);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])->putJson("/api/v1/communities/{$community->id}", [
            'name' => 'Updated Community Name',
            'description' => 'Updated desc',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertDatabaseHas(Community::TABLE, [
            'id' => $community->id,
            'name' => 'Updated Community Name',
        ]);
    }

    public function test_delete_community()
    {
        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $community = $this->createCommunity($creator);
        $token = auth('api')->login($creator);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])->deleteJson("/api/v1/communities/{$community->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertDatabaseMissing(Community::TABLE, [
            'id' => $community->id,
        ]);
    }

    public function test_takedown_community()
    {
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $community = $this->createCommunity($creator);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])->postJson("/api/v1/communities/{$community->id}/takedown", [
            'notes' => 'Violation of terms',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertFalse($community->fresh()->is_active);
    }

    public function test_restore_community()
    {
        $admin = $this->createUserWithRole(RoleEnum::ADMIN->value);
        $token = auth('api')->login($admin);

        $creator = $this->createUserWithRole(RoleEnum::PROVIDER->value);
        $community = $this->createCommunity($creator);
        // Manually takedown first
        $community->update(['is_active' => false]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])->postJson("/api/v1/communities/{$community->id}/restore", [
            'notes' => 'Cleared',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'status' => 'success',
            ]);

        $this->assertTrue($community->fresh()->is_active);
    }
}
