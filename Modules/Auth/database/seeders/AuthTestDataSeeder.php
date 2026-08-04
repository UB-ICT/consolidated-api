<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Models\Menu;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;

class AuthTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $targetRoleNames = [
            'super-admin',
            'budget-officer',
            'president',
            'vice-president',
            'director-of-finance',
            'director-dean',
            'requester',
            'purchase-officer',
            // Kept for budget pipeline / future assignments (may not have users yet).
            'senior-account',
            'accounts-payable',
        ];

        $roleMap = [];
        foreach ($targetRoleNames as $name) {
            $role = Role::firstOrCreate(
                ['role_name' => $name],
                [
                    'id' => Str::uuid()->toString(),
                    'description' => 'System access role for '.ucfirst($name),
                ]
            );
            $roleMap[$name] = $role->id;
        }

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
            $menuApp = Menu::firstOrNew(['path' => $appData['path'], 'parent_id' => null]);

            if (!$menuApp->exists) {
                $menuApp->id = Str::uuid()->toString();
            }

            $menuApp->fill([
                'label' => $appData['label'],
                'icon' => $appData['icon'],
                'type' => Menu::TYPE_APPLICATION,
                'status' => Menu::STATUS_ACTIVE,
                'description' => $appData['description'],
                'category' => $appData['category'],
                'sort_order' => 1,
            ])->save();

            // Application roots are public; visibility comes from child menus.
            $menuApp->roles()->sync([]);
            $appIds[$key] = $menuApp->id;
        }

        $requisitionMenuItems = [
            ['label' => 'Dashboard', 'path' => '/requisitions', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'Requisition', 'path' => '/requisitions/forms', 'icon' => 'document-plus', 'sort_order' => 2],
            ['label' => 'Suppliers', 'path' => '/requisitions/suppliers', 'icon' => 'building-office', 'sort_order' => 3],
            ['label' => 'Accounts', 'path' => '/requisitions/accounts', 'icon' => 'notebook', 'sort_order' => 4],
            ['label' => 'Cost Centers', 'path' => '/requisitions/cost-centers', 'icon' => 'building-2', 'sort_order' => 5],
            ['label' => 'Budget', 'path' => '/requisitions/budgets', 'icon' => 'clipboard-list', 'sort_order' => 6],
            ['label' => 'Pipelines', 'path' => '/requisitions/pipelines', 'icon' => 'git-branch', 'sort_order' => 7],
        ];

        $allRoleIds = array_values($roleMap);

        foreach ($requisitionMenuItems as $item) {
            $this->upsertMenuItem(
                parentId: $appIds['requisition'],
                path: $item['path'],
                type: Menu::TYPE_SUBMENU,
                attributes: [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'sort_order' => $item['sort_order'],
                    'status' => Menu::STATUS_ACTIVE,
                ],
                roleIds: $allRoleIds
            );
        }

        $signOut = $this->upsertMenuItem(
            parentId: null,
            path: '/signOut',
            type: Menu::TYPE_USER_MENU,
            attributes: [
                'label' => 'Sign out',
                'icon' => 'sign-out',
                'sort_order' => 3,
                'status' => Menu::STATUS_ACTIVE,
            ],
            roleIds: []
        );

        $adminConsole = $this->upsertMenuItem(
            parentId: null,
            path: '/admin',
            type: Menu::TYPE_USER_MENU,
            attributes: [
                'label' => 'Admin Console',
                'icon' => 'squares-plus',
                'sort_order' => 4,
                'status' => Menu::STATUS_ACTIVE,
            ],
            roleIds: [$roleMap['super-admin']]
        );

        $adminConsoleMenuItems = [
            ['label' => 'Overview', 'path' => '/admin', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'Users', 'path' => '/admin/users', 'icon' => 'users', 'sort_order' => 2],
            ['label' => 'Apps', 'path' => '/admin/apps', 'icon' => 'shield-check', 'sort_order' => 3],
            ['label' => 'Roles', 'path' => '/admin/roles', 'icon' => 'shield-check', 'sort_order' => 4],
            ['label' => 'Menus', 'path' => '/admin/menu', 'icon' => 'bars-3', 'sort_order' => 5],
            ['label' => 'Access Requests', 'path' => '/admin/access-requests', 'icon' => 'shield-check', 'sort_order' => 6],
            ['label' => 'Audit log', 'path' => '/admin/audit-log', 'icon' => 'shield-check', 'sort_order' => 7],
        ];

        foreach ($adminConsoleMenuItems as $item) {
            $this->upsertMenuItem(
                parentId: $adminConsole->id,
                path: $item['path'],
                type: Menu::TYPE_SUBMENU,
                attributes: [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'sort_order' => $item['sort_order'],
                    'status' => Menu::STATUS_ACTIVE,
                ],
                roleIds: [$roleMap['super-admin']]
            );
        }

        unset($signOut);

        $testUsers = [
            ['email' => 'james.faber@ub.edu.bz', 'name' => 'James Faber', 'role' => 'super-admin'],
            ['email' => 'luis.herrera@ub.edu.bz', 'name' => 'Luis Herrera', 'role' => 'super-admin'],
        ];

        foreach ($testUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $user->roles()->syncWithoutDetaching([$roleMap[$userData['role']]]);
        }

        $this->command?->info('Successfully seeded menus with role_menu pivot assignments.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $roleIds
     */
    private function upsertMenuItem(
        ?string $parentId,
        string $path,
        string $type,
        array $attributes,
        array $roleIds
    ): Menu {
        $menu = Menu::firstOrNew([
            'path' => $path,
            'parent_id' => $parentId,
            'type' => $type,
        ]);

        if (!$menu->exists) {
            $menu->id = Str::uuid()->toString();
        }

        $menu->fill($attributes)->save();
        $menu->roles()->sync($roleIds);

        return $menu;
    }
}
