<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            // =========================================================================
            // 1. ORIGINAL TEST ROLES
            // =========================================================================
            $superAdminRole = Role::query()->updateOrCreate(
                ['role_name' => 'Super Admin'],
                ['description' => 'Full access role for testing']
            );

            $staffRole = Role::query()->updateOrCreate(
                ['role_name' => 'Staff'],
                ['description' => 'General staff role for testing']
            );

            // =========================================================================
            // 2. NEW REAL APPLICATION ROLES
            // =========================================================================
            $appRoles = [
                // Public Safety Roles
                'api_public_safety_Admin@ub.edu.bz'    => 'Chief Public Safety Officer and System Administrators',
                'api_public_safety_Security@ub.edu.bz' => 'Shift Supervisors',
                'api_public_safety_Officer@ub.edu.bz'  => 'Public Safety Officers',

                // Annual Report Roles
                'api_annual_report_Developers@ub.edu.bz' => 'Annual Report Developers',
                'api_annual_report_HR@ub.edu.bz'        => 'Annual Report HR Personnel',
                'api_annual_report_Finance@ub.edu.bz'   => 'Annual Report Finance Personnel',
                'api_annual_report_Records@ub.edu.bz'   => 'Annual Report Records Personnel',
                'api_annual_report_Directors@ub.edu.bz' => 'Annual Report Directors',
                'api_annual_report_Admin@ub.edu.bz'     => 'Annual Report Administrators',
                'api_annual_report_Deans@ub.edu.bz'     => 'Annual Report Deans',
            ];

            $createdAppRoles = [];
            foreach ($appRoles as $name => $description) {
                $createdAppRoles[$name] = Role::query()->updateOrCreate(
                    ['role_name' => $name],
                    ['description' => $description]
                );
            }

            // =========================================================================
            // 3. ORIGINAL PERMISSIONS CONFIGURATION
            // =========================================================================
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
            });

            $superAdminRole->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            $staffRole->permissions()->syncWithoutDetaching([$permissions->firstWhere('action_name', 'users_view')->id]);

            // =========================================================================
            // 4. USERS CREATION & ASSIGNMENT
            // =========================================================================
            $adminUser = User::query()->updateOrCreate(
                ['email' => 'mock.user@example.com'],
                [
                    'name' => 'Postman Mock User',
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
                    'domain' => 'ub.edu.bz',
                    'password' => Hash::make('Passw0rd!23'),
                    'google_id' => 'staff-test-user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            $adminUser->roles()->syncWithoutDetaching([$superAdminRole->id]);
            $staffUser->roles()->syncWithoutDetaching([$staffRole->id]);

            // =========================================================================
            // 5. REAL ACCOUNT BOOTSTRAPPING
            // =========================================================================

            // Assign Luis Herrera his roles
            $luisEmail = 'luis.herrera@ub.edu.bz';
            $luisUser = User::query()->where('email', $luisEmail)->first();
            if ($luisUser) {
                $luisUser->roles()->syncWithoutDetaching([
                    $createdAppRoles['api_public_safety_Admin@ub.edu.bz']->id,
                    $createdAppRoles['api_annual_report_Admin@ub.edu.bz']->id,
                ]);
            }

            // Assign James Faber his public safety admin role
            $jamesEmail = 'james.faber@ub.edu.bz';
            $jamesUser = User::query()->where('email', $jamesEmail)->first();
            if ($jamesUser) {
                $jamesUser->roles()->syncWithoutDetaching([
                    $createdAppRoles['api_public_safety_Admin@ub.edu.bz']->id,
                ]);
            }

            // =========================================================================
            // 6. ORIGINAL MENUS GENERATION
            // =========================================================================
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
