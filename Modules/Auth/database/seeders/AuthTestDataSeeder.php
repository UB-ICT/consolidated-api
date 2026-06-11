<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
        // 1. Roles core list
        $targetRoleNames = [
            'super-admin',
            'budget-officer',
            'president',
            'vice-president',
            'director-of-finance',
            'accounts-payable',
            'senior-account',
            'director/dean',
            'requester',
            'developer',
        ];

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

        // 2. Define Level 1 Applications
        $apps = [
            'requisition' => [
                'label' => 'Requisition System',
                'path'  => '/requisitions',
                'icon'  => 'briefcase',
            ],
            'public_safety' => [
                'label' => 'Public Safety',
                'path'  => '/public-safety',
                'icon'  => 'shield-check',
            ],
            'ub_forms' => [
                'label' => 'UB Annual Reports',
                'path'  => '/ub-annual-reports',
                'icon'  => 'clipboard-document-list',
            ],
        ];

        $appIds = [];
        foreach ($apps as $key => $appData) {
            $menuApp = Menu::updateOrCreate(
                ['path' => $appData['path'], 'parent_id' => null],
                [
                    'id'         => Str::uuid()->toString(),
                    'label'      => $appData['label'],
                    'icon'       => $appData['icon'],
                    'type'       => 'application',
                    'sort_order' => 1,
                ]
            );
            $appIds[$key] = $menuApp->id;
        }

        // 3. Define Level 2 shared management items
        $managementMenuItems = [
            ['label' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'Approval inbox', 'path' => '/requisitions/approval-inbox', 'icon' => 'inbox', 'sort_order' => 2],
            ['label' => 'All forms', 'path' => '/requisitions/all-forms', 'icon' => 'clipboard-document-list', 'sort_order' => 3],
            ['label' => 'Budgets', 'path' => '/budgets', 'icon' => 'chart-bar', 'sort_order' => 4],
            ['label' => 'Suppliers', 'path' => '/suppliers', 'icon' => 'building-office', 'sort_order' => 5],
        ];

        // 4. Define Level 2 Requester items
        $costCenterMenuItems = [
            ['label' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'New requisition', 'path' => '/requisitions/create', 'icon' => 'document-plus', 'sort_order' => 2],
            ['label' => 'My forms', 'path' => '/requisitions/my-forms', 'icon' => 'clipboard-document-list', 'sort_order' => 3],
            ['label' => 'My budget', 'path' => '/my-budget', 'icon' => 'chart-bar', 'sort_order' => 4],
            ['label' => 'Suppliers', 'path' => '/suppliers', 'icon' => 'building-office', 'sort_order' => 5],
        ];

        // 5. Seed management layouts
        $managementRoles = array_diff($targetRoleNames, ['requester']);
        foreach ($managementRoles as $roleName) {
            $roleId = $roleMap[$roleName];
            foreach ($managementMenuItems as $item) {
                Menu::updateOrCreate(
                    [
                        'path'    => $item['path'],
                        'role_id' => $roleId
                    ],
                    [
                        'id'         => Str::uuid()->toString(),
                        'label'      => $item['label'],
                        'icon'       => $item['icon'],
                        'type'       => 'submenu',
                        'parent_id'  => $appIds['requisition'],
                        'sort_order' => $item['sort_order'],
                    ]
                );
            }
        }

        // 6. Seed requester layout
        $costCenterId = $roleMap['requester'];
        foreach ($costCenterMenuItems as $item) {
            Menu::updateOrCreate(
                [
                    'path'    => $item['path'],
                    'role_id' => $costCenterId
                ],
                [
                    'id'         => Str::uuid()->toString(),
                    'label'      => $item['label'],
                    'icon'       => $item['icon'],
                    'type'       => 'submenu',
                    'parent_id'  => $appIds['requisition'],
                    'sort_order' => $item['sort_order'],
                ]
            );
        }

        // 7. Seed Profile Dropdown Cards
        $profileDropdownItems = [
            ['label' => 'View profile', 'path' => '/profile', 'icon' => 'user', 'role_id' => null, 'sort_order' => 1],
            ['label' => 'Settings', 'path' => '/settings', 'icon' => 'cog', 'role_id' => null, 'sort_order' => 2],
            ['label' => 'Sign out', 'path' => '/signOut', 'icon' => 'sign-out', 'role_id' => null, 'sort_order' => 3],
            ['label' => 'Admin tools', 'path' => '/admin', 'icon' => 'squares-plus', 'role_id' => $roleMap['super-admin'], 'sort_order' => 4],
        ];

        foreach ($profileDropdownItems as $item) {
            Menu::updateOrCreate(
                [
                    'path'    => $item['path'],
                    'type'    => 'user-menu',
                    'role_id' => $item['role_id']
                ],
                [
                    'id'         => Str::uuid()->toString(),
                    'label'      => $item['label'],
                    'icon'       => $item['icon'],
                    'parent_id'  => null,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }

        // 8. Seed External links
        $externalLinks = [
            ['label' => 'External Tools', 'path' => '/external-tools', 'icon' => 'wrench', 'sort_order' => 5],
            ['label' => 'Library Docs', 'path' => '/docs', 'icon' => 'book-open', 'sort_order' => 6],
            ['label' => 'University Website', 'path' => 'https://www.ub.edu.bz', 'icon' => 'globe-alt', 'sort_order' => 7],
        ];

        foreach ($externalLinks as $link) {
            Menu::updateOrCreate(
                ['path' => $link['path'], 'type' => 'external-link'],
                [
                    'id'         => Str::uuid()->toString(),
                    'label'      => $link['label'],
                    'icon'       => $link['icon'],
                    'role_id'    => null,
                    'parent_id'  => null,
                    'sort_order' => $link['sort_order'],
                ]
            );
        }

        // 9. Define testing users distributed by role
        $testUsers = [
            ['email' => 'james.faber@ub.edu.bz', 'name' => 'James Faber', 'role' => 'director/dean', 'cost_center_id' => 2], // 🔑 Director
            ['email' => 'zariya.obi@ub.edu.bz', 'name' => 'Zariya Obi', 'role' => 'requester', 'cost_center_id' => 2],     // 📝 Requester
            ['email' => 'luis.herrera@ub.edu.bz', 'name' => 'Luis Herrera', 'role' => 'super-admin', 'cost_center_id' => 2], // 👑 Super Admin
        ];

        // 10. User instantiation and layout matching
        foreach ($testUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'id'       => Str::uuid()->toString(),
                    'name'     => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            // 💡 Explicitly populating user_cost_center pivot relationship table for metrics scoping
            DB::table('user_cost_center')->updateOrInsert(
                [
                    'user_id'        => $user->id,
                    'cost_center_id' => $userData['cost_center_id']
                ],
                [
                    'created_at'     => now(),
                    'updated_at'     => now()
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
