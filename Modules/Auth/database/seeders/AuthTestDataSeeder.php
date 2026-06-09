<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Auth\Models\Menu;
use Modules\Auth\Models\Role;

class AuthTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. All roles needed across your requisition system variations
        $targetRoleNames = [
            'budget-officer',
            'president',
            'vice-president',
            'director-of-finance',
            'accounts-payable',
            'senior-account',
            'director/dean',
            'cost-center' // 👈 Added the cost-center role
        ];

        // 2. Guarantee roles exist and capture their database UUIDs safely
        $roleMap = [];
        foreach ($targetRoleNames as $name) {
            // Find the role by name, or instantiate a new one with a fresh UUID
            $role = Role::firstOrCreate(
                ['role_name' => $name],
                [
                    'id' => Str::uuid()->toString(),
                    'description' => "System access role for " . ucfirst($name),
                ]
            );
            $roleMap[$name] = $role->id; // Map key names directly to their stable UUID
        }

        // 3. Define the menu items shared by the 7 management/approval roles
        $managementMenuItems = [
            ['label' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'Approval inbox', 'path' => '/requisitions/approval-inbox', 'icon' => 'inbox', 'sort_order' => 2],
            ['label' => 'All forms', 'path' => '/requisitions/all-forms', 'icon' => 'clipboard-document-list', 'sort_order' => 3],
            ['label' => 'Budgets', 'path' => '/budgets', 'icon' => 'chart-bar', 'sort_order' => 4],
            ['label' => 'Suppliers', 'path' => '/suppliers', 'icon' => 'building-office', 'sort_order' => 5],
        ];

        // 4. Define the menu items specific to your 'cost-center' role layout
        $costCenterMenuItems = [
            ['label' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'squares-2x2', 'sort_order' => 1],
            ['label' => 'New requisition', 'path' => '/requisitions/create', 'icon' => 'document-plus', 'sort_order' => 2],
            ['label' => 'My forms', 'path' => '/requisitions/my-forms', 'icon' => 'clipboard-document-list', 'sort_order' => 3],
            ['label' => 'My budget', 'path' => '/my-budget', 'icon' => 'chart-bar', 'sort_order' => 4],
            ['label' => 'Suppliers', 'path' => '/suppliers', 'icon' => 'building-office', 'sort_order' => 5],
        ];

        // 5. Seed the 7 management roles
        $managementRoles = array_diff($targetRoleNames, ['cost-center']);
        foreach ($managementRoles as $roleName) {
            $roleId = $roleMap[$roleName];
            foreach ($managementMenuItems as $item) {
                Menu::updateOrCreate(
                    ['path' => $item['path'], 'role_id' => $roleId],
                    [
                        'id'         => Str::uuid()->toString(),
                        'label'      => $item['label'],
                        'icon'       => $item['icon'],
                        'parent_id'  => null,
                        'sort_order' => $item['sort_order'],
                    ]
                );
            }
        }

        // 6. Seed the cost-center role explicitly using its unique layout array
        $costCenterId = $roleMap['cost-center'];
        foreach ($costCenterMenuItems as $item) {
            Menu::updateOrCreate(
                ['path' => $item['path'], 'role_id' => $costCenterId],
                [
                    'id'         => Str::uuid()->toString(),
                    'label'      => $item['label'],
                    'icon'       => $item['icon'],
                    'parent_id'  => null,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }

        $this->command->info('Successfully seeded all modular role sidebar menus!');
    }
}
