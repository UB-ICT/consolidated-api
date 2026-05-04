<?php

namespace Modules\UBPortal\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\UBPortal\Models\Group;
use Modules\UBPortal\Models\Role;
use Modules\UBPortal\Models\Permission;
use Modules\UBPortal\Models\Application;

class UBPortalDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Core Applications
        $financeApp = Application::create([
            'app_name' => 'UB Finance',
            'description' => 'Student accounts and payroll'
        ]);

        $identityApp = Application::create([
            'app_name' => 'UB Identity Cloud',
            'description' => 'IAM and Portal Management'
        ]);

        // 2. Create Core Permissions
        $viewUsers = Permission::create(['category' => 'User Management', 'action_name' => 'view_users']);
        $editUsers = Permission::create(['category' => 'User Management', 'action_name' => 'edit_users']);
        $viewAudit = Permission::create(['category' => 'Security', 'action_name' => 'view_audit_logs']);

        // 3. Create Roles and Attach Permissions
        $superAdmin = Role::create([
            'role_name' => 'Super Admin',
            'description' => 'Full System Access'
        ]);
        $superAdmin->permissions()->attach([$viewUsers->id, $editUsers->id, $viewAudit->id]);

        $registrar = Role::create([
            'role_name' => 'Registrar',
            'description' => 'Student Record Management'
        ]);
        $registrar->permissions()->attach([$viewUsers->id]);

        // 4. Create the Identity Nesting (Groups)
        $ictGroup = Group::create([
            'group_name' => 'ICT Department',
            'description' => 'Information Technology & Infrastructure'
        ]);
        // Inheritance: The ICT Group gets the Super Admin Role
        $ictGroup->roles()->attach($superAdmin->id);

        // 5. Create your First Admin User (You!)
        $admin = User::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'name' => 'System Administrator',
            'email' => 'admin@ub.edu.bz',
            'type' => 'staff',
            'department' => 'ICT',
            'status' => 'active',
        ]);

        // Assign the user to the ICT Group
        $admin->groups()->attach($ictGroup->id);
    }
}
