<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\Menu;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;

class AuthTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Add 'super-admin' to your role core list
        $targetRoleNames = [
            'super-admin',
            'budget-officer',
            'president',
            'vice-president',
            'director-of-finance',
            'accounts-payable',
            'senior-account',
            'director-dean',
            'requester',
            'purchase-officer'
        ];

        // Guarantee roles exist and capture their database UUIDs safely
        $roleMap = [];
        foreach ($targetRoleNames as $name) {
            $role = Role::firstOrCreate(
                ['role_name' => $name],
                [
                    'id' => Str::uuid()->toString(),
                    'description' => "System access role for " . ucfirst($name),
                ]
            );
            $roleMap[$name] = $role->id;
        }

        // 2. Define the Level 1 Applications as root menus (parent_id = null, type = 'application')
        $apps = [
            'ub_portal' => [
                'label' => 'UB Portal',
                'path' => '/',
                'icon' => 'squares-plus',
                'description' => 'Central hub for university applications and services.',
                'category' => 'Administrative',
            ],
            'requisition' => [
                'label' => 'Requisition System',
                'path' => '/requisitions',
                'icon' => 'briefcase',
                'description' => 'Submit and track purchase requisitions and approvals.',
                'category' => 'Finance',
            ],
            'public_safety' => [
                'label' => 'Public Safety',
                'path' => '/public-safety',
                'icon' => 'shield-check',
                'description' => 'Campus safety alerts, incident reporting, and resources.',
                'category' => 'Administrative',
            ],
            'ub_forms' => [
                'label' => 'UB Annual Reports',
                'path' => '/ub-annual-reports',
                'icon' => 'clipboard-document-list',
                'description' => 'View and submit university annual reports.',
                'category' => 'Academic',
            ],
        ];

        $appIds = [];
        foreach ($apps as $key => $appData) {
            // Use firstOrNew to prevent overriding the 'id' during updates
            $menuApp = Menu::firstOrNew(['path' => $appData['path'], 'parent_id' => null]);

            if (!$menuApp->exists) {
                $menuApp->id = Str::uuid()->toString();
            }

            $menuApp->fill([
                'label' => $appData['label'],
                'icon' => $appData['icon'],
                'type' => 'application',
                'status' => Menu::STATUS_ACTIVE,
                'description' => $appData['description'],
                'category' => $appData['category'],
                'sort_order' => 1,
            ])->save();

            $appIds[$key] = $menuApp->id;
        }

        // 3. Define the Level 2 menu items shared by the management/approval roles
        $managementMenuItems = [
            ['label' => 'Dashboard', 'path' => '/requisitions', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'Requisition', 'path' => '/requisitions/forms', 'icon' => 'document-plus', 'sort_order' => 2],
            ['label' => 'Suppliers', 'path' => '/requisitions/suppliers', 'icon' => 'building-office', 'sort_order' => 3],
        ];

        // 4. Define the Level 2 menu items specific to your 'cost-center' role layout
        $costCenterMenuItems = [
            ['label' => 'Dashboard', 'path' => '/requisitions', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'Requisition', 'path' => '/requisitions/forms', 'icon' => 'document-plus', 'sort_order' => 2],
            ['label' => 'Suppliers', 'path' => '/requisitions/suppliers', 'icon' => 'building-office', 'sort_order' => 3],
        ];

        // 5. Seed management layouts nested under the RequisitionSystem app item
        $managementRoles = array_diff($targetRoleNames, ['requester']);
        foreach ($managementRoles as $roleName) {
            $roleId = $roleMap[$roleName];
            foreach ($managementMenuItems as $item) {
                $subMenu = Menu::firstOrNew([
                    'path' => $item['path'],
                    'role_id' => $roleId
                ]);

                if (!$subMenu->exists) {
                    $subMenu->id = Str::uuid()->toString();
                }

                $subMenu->fill([
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'type' => 'submenu',
                    'parent_id' => $appIds['requisition'],
                    'sort_order' => $item['sort_order'],
                ])->save();
            }
        }

        // 6. Seed cost-center layout nested under the RequisitionSystem app item
        $costCenterId = $roleMap['requester'];
        foreach ($costCenterMenuItems as $item) {
            $subMenu = Menu::firstOrNew([
                'path' => $item['path'],
                'role_id' => $costCenterId
            ]);

            if (!$subMenu->exists) {
                $subMenu->id = Str::uuid()->toString();
            }

            $subMenu->fill([
                'label' => $item['label'],
                'icon' => $item['icon'],
                'type' => 'submenu',
                'parent_id' => $appIds['requisition'],
                'sort_order' => $item['sort_order'],
            ])->save();
        }

        // 7. Seed Profile Dropdown Cards (type = 'user-menu')
        $profileDropdownItems = [
            'sign_out' => ['label' => 'Sign out', 'path' => '/signOut', 'icon' => 'sign-out', 'role_id' => null, 'sort_order' => 3],
            'admin_console' => ['label' => 'Admin Console', 'path' => '/admin', 'icon' => 'squares-plus', 'role_id' => $roleMap['super-admin'], 'sort_order' => 4],
        ];

        $userMenuIds = [];
        foreach ($profileDropdownItems as $key => $item) {
            // Keyed on role_id too, since a menu item can have one role-scoped row per role.
            $userMenu = Menu::firstOrNew([
                'path' => $item['path'],
                'type' => 'user-menu',
                'role_id' => $item['role_id'],
            ]);

            if (!$userMenu->exists) {
                $userMenu->id = Str::uuid()->toString();
            }

            $userMenu->fill([
                'label' => $item['label'],
                'icon' => $item['icon'],
                'parent_id' => null,
                'sort_order' => $item['sort_order'],
            ])->save();

            $userMenuIds[$key] = $userMenu->id;
        }

        // 8. Seed submenu layout nested under the Admin Console user-menu item.
        // getActiveApplicationMenu() only needs the root menu's id and loads its
        // type=submenu children — it doesn't require the root to be type=application,
        // so Admin Console can stay a super-admin-only profile link and still drive
        // the sidebar via the same endpoint the other apps use.
        $adminConsoleMenuItems = [
            ['label' => 'Overview', 'path' => '/admin', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'Users', 'path' => '/admin/users', 'icon' => 'users', 'sort_order' => 2],
            ['label' => 'Apps', 'path' => '/admin/apps', 'icon' => 'shield-check', 'sort_order' => 3],
            ['label' => 'Roles', 'path' => '/admin/roles', 'icon' => 'shield-check', 'sort_order' => 4],
            ['label' => 'Access Requests', 'path' => '/admin/access-requests', 'icon' => 'shield-check', 'sort_order' => 5],
            ['label' => 'Audit log', 'path' => '/admin/audit-log', 'icon' => 'shield-check', 'sort_order' => 6],
        ];

        foreach ($adminConsoleMenuItems as $item) {
            $subMenu = Menu::firstOrNew([
                'path' => $item['path'],
                'role_id' => $roleMap['super-admin'],
                'parent_id' => $userMenuIds['admin_console'],
            ]);

            if (!$subMenu->exists) {
                $subMenu->id = Str::uuid()->toString();
            }

            $subMenu->fill([
                'label' => $item['label'],
                'icon' => $item['icon'],
                'type' => 'submenu',
                'sort_order' => $item['sort_order'],
            ])->save();
        }

        // 9. Define testing users
        $testUsers = [
            ['email' => 'james.faber@ub.edu.bz', 'name' => 'James Faber', 'role' => 'super-admin'],
            ['email' => 'luis.herrera@ub.edu.bz', 'name' => 'Luis Herrera', 'role' => 'super-admin'],
        ];

        // 10. Handle user instantiation and pivot syncs
        foreach ($testUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $roleId = $roleMap[$userData['role']];

            if (method_exists($user, 'roles')) {
                $user->roles()->syncWithoutDetaching([$roleId]);
            }
        }

        $this->command->info('Successfully seeded everything including user-menus and external layout links!');
    }
}
