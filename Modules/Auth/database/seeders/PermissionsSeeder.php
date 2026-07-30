<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the fixed permission vocabulary that the Admin Console's
     * EnsureUserHasPermission middleware checks against. super-admin bypasses
     * these entirely (see User::hasPermission()), so nothing here is attached
     * to a role automatically - that's a deliberate choice made per-role via
     * the Roles admin page's "Manage permissions" dialog.
     */
    public function run(): void
    {
        $permissions = [
            'Users' => [
                'view_users',
                'create_users',
                'edit_users',
                'delete_users',
                'manage_user_roles',
            ],
            'Roles' => [
                'view_roles',
                'create_roles',
                'edit_roles',
                'delete_roles',
                'manage_role_permissions',
            ],
            'Permissions' => [
                'view_permissions',
                'manage_permissions',
            ],
            'Applications' => [
                'view_applications',
                'manage_applications',
            ],
        ];

        foreach ($permissions as $category => $actionNames) {
            foreach ($actionNames as $actionName) {
                Permission::firstOrCreate([
                    'category' => $category,
                    'action_name' => $actionName,
                ]);
            }
        }

        $this->command->info('Seeded the core Admin Console permission list.');
    }
}
