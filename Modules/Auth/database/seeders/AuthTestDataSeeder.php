<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\Menu;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;

class AuthTestDataSeeder extends Seeder
{
    /**
     * Seed predictable Auth test data for controller/API testing.
     */
    public function run(): void
    {
        DB::connection('pgsql')->transaction(function (): void {
            $superAdminRole = Role::query()->updateOrCreate(
                ['role_name' => 'Super Admin'],
                ['description' => 'Full access role for testing']
            );

            $staffRole = Role::query()->updateOrCreate(
                ['role_name' => 'Staff'],
                ['description' => 'General staff role for testing']
            );

            $permissions = collect([
                ['category' => 'users', 'action_name' => 'users_view'],
                ['category' => 'users', 'action_name' => 'users_create'],
                ['category' => 'roles', 'action_name' => 'roles_manage'],
                ['category' => 'menus', 'action_name' => 'menus_manage'],
            ])->map(function (array $permission) {
                return Permission::query()->updateOrCreate(
                    ['action_name' => $permission['action_name']],
                    ['category' => $permission['category']]
                );
            })->keyBy('action_name');

            $superAdminRole->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());

            // Guard against null access if permission definitions change.
            if ($permissions->has('users_view')) {
                $staffRole->permissions()->syncWithoutDetaching([$permissions->get('users_view')->id]);
            }


            $adminUser = User::query()->updateOrCreate(
                ['email' => 'mock.user@example.com'],
                [
                    'name' => 'Postman Mock User',
                    'guid' => 'mock-user-guid',
                    'domain' => 'ub.edu.bz',
                    'password' => Hash::make('Passw0rd!23'),
                    'google_id' => 'postman-mock-user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            $staffUser = User::query()->updateOrCreate(
                ['email' => 'staff.user@example.com'],
                [
                    'name' => 'Staff Test User',
                    'guid' => 'staff-user-guid',
                    'domain' => 'ub.edu.bz',
                    'password' => Hash::make('Passw0rd!23'),
                    'google_id' => 'staff-test-user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            $adminUser->roles()->syncWithoutDetaching([$superAdminRole->id]);

            $staffUser->roles()->syncWithoutDetaching([$staffRole->id]);

            Menu::query()->updateOrCreate(
                ['path' => '/dashboard'],
                [
                    'label' => 'Dashboard',
                    'icon' => 'mdi-view-dashboard',
                    'sort_order' => 1,
                    'role_id' => null,
                    'parent_id' => null,
                ]
            );

            Menu::query()->updateOrCreate(
                ['path' => '/admin/users'],
                [
                    'label' => 'Manage Users',
                    'icon' => 'mdi-account-cog',
                    'sort_order' => 2,
                    'role_id' => $superAdminRole->id,
                    'parent_id' => null,
                ]
            );
        });
    }
}
