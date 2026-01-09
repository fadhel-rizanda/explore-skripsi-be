<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan optimize:clear
     * php artisan db:seed --class=RolePermissionSeeder
     */
    public function run(): void
    {
        // ----- CREATE ROLES -----
        $roles = ['admin', 'adopter', 'provider'];
        foreach ($roles as $roleName) {
            if (! Role::where('name', $roleName)->exists()) {
                Role::create([
                    'name' => $roleName,
                    'guard_name' => 'api',
                ]);
            }
        }

        $admin = Role::findByName('admin', 'api');
        $adopter = Role::findByName('adopter', 'api');
        $provider = Role::findByName('provider', 'api');

        // ----- CREATE PERMISSIONS -----
        $permissions = [
            // User & Profile
            'view-profile',
            'edit-profile',
            'delete-account',

            // Adoption
            'view-animals',
            'adopt-animal',
            'approve-adoption', // provider only

            // Documents
            'upload-document',
            'view-document',
            'delete-document',

            // Community & Events
            'create-community',
            'edit-community',
            'delete-community',
            'join-community',
            'create-event',
            'manage-event',

            // Notifications
            'view-notifications',
            'send-notifications',

            // Admin only
            'manage-users',
            'manage-roles',
            'manage-permissions',
        ];

        foreach ($permissions as $permission) {
            if (! Permission::where('name', $permission)
                ->where('guard_name', 'api')
                ->exists()) {
                Permission::create([
                    'name' => $permission,
                    'guard_name' => 'api',
                ]);
            }

        }

        // ----- ASSIGN PERMISSIONS TO ROLES -----
        $adopter->givePermissionTo([
            'view-profile',
            'edit-profile',
            'view-animals',
            'adopt-animal',
            'view-document',
            'join-community',
        ]);

        $provider->givePermissionTo([
            'view-profile',
            'edit-profile',
            'upload-document',
            'view-document',
            'delete-document',
            'approve-adoption',
            'create-community',
            'edit-community',
            'delete-community',
            'create-event',
            'manage-event',
            'send-notifications',
        ]);

        $admin->givePermissionTo(Permission::all());

        $this->command->info('Roles and permissions have been successfully assigned!');
    }
}
