<?php

namespace Modules\UBPortal\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\UBPortal\Models\AccessRequest;
use Modules\UBPortal\Models\AuditLog;
use Modules\UBPortal\Models\Group;
use Modules\UBPortal\Models\MenuItem;
use Modules\UBPortal\Models\Role;
use Modules\UBPortal\Models\Permission;
use Modules\UBPortal\Models\Application;

class UBPortalDatabaseSeeder extends Seeder
{
    /**
     * Seed a complete UBPortal access-management example.
     *
     * Flow demonstrated:
     * application -> permissions -> roles -> groups -> users
     * -> menu items -> access request -> audit trail.
     */
    public function run(): void
    {
        // 1. Create the applications users will request or manage access for.
        $financeApp = Application::firstOrCreate([
            'app_name' => 'UB Finance',
        ], [
            'description' => 'Student accounts and payroll'
        ]);

        $identityApp = Application::firstOrCreate([
            'app_name' => 'UB Identity Cloud',
        ], [
            'description' => 'IAM and Portal Management'
        ]);

        // 2. Create permissions that can later be bundled into roles.
        $viewUsers = Permission::firstOrCreate([
            'action_name' => 'view_users',
        ], [
            'category' => 'User Management',
        ]);

        $editUsers = Permission::firstOrCreate([
            'action_name' => 'edit_users',
        ], [
            'category' => 'User Management',
        ]);

        $viewAudit = Permission::firstOrCreate([
            'action_name' => 'view_audit_logs',
        ], [
            'category' => 'Security',
        ]);

        $requestFinanceAccess = Permission::firstOrCreate([
            'action_name' => 'request_finance_access',
        ], [
            'category' => 'Access Requests',
        ]);

        // 3. Create roles and attach both permissions and application scope.
        $superAdmin = Role::firstOrCreate([
            'role_name' => 'Super Admin',
        ], [
            'description' => 'Full System Access'
        ]);
        $superAdmin->permissions()->syncWithoutDetaching([
            $viewUsers->id,
            $editUsers->id,
            $viewAudit->id,
        ]);
        $superAdmin->applications()->syncWithoutDetaching([$identityApp->id]);

        $registrar = Role::firstOrCreate([
            'role_name' => 'Registrar',
        ], [
            'description' => 'Student Record Management'
        ]);
        $registrar->permissions()->syncWithoutDetaching([
            $viewUsers->id,
            $requestFinanceAccess->id,
        ]);
        $registrar->applications()->syncWithoutDetaching([$financeApp->id]);

        $financeReviewer = Role::firstOrCreate([
            'role_name' => 'Finance Reviewer',
        ], [
            'description' => 'Reviews finance-related access requests'
        ]);
        $financeReviewer->permissions()->syncWithoutDetaching([
            $viewUsers->id,
            $viewAudit->id,
        ]);
        $financeReviewer->applications()->syncWithoutDetaching([$financeApp->id]);

        // 4. Create groups to demonstrate inherited access through membership.
        $ictGroup = Group::firstOrCreate([
            'group_name' => 'ICT Department',
        ], [
            'description' => 'Information Technology & Infrastructure'
        ]);
        $ictGroup->roles()->syncWithoutDetaching([$superAdmin->id]);

        $registrarOffice = Group::firstOrCreate([
            'group_name' => 'Registrar Office',
        ], [
            'description' => 'Staff responsible for student records and enrollment'
        ]);
        $registrarOffice->roles()->syncWithoutDetaching([$registrar->id]);

        // 5. Create example users for each stage of the flow.
        $admin = User::query()->where('email', 'admin@ub.edu.bz')->first();
        if (! $admin) {
            $admin = User::query()->forceCreate([
                'id' => (string) Str::uuid(),
                'name' => 'System Administrator',
                'email' => 'admin@ub.edu.bz',
                'type' => 'staff',
                'department' => 'ICT',
                'status' => 'active',
            ]);
        }

        $registrarUser = User::query()->where('email', 'registrar@ub.edu.bz')->first();
        if (! $registrarUser) {
            $registrarUser = User::query()->forceCreate([
                'id' => (string) Str::uuid(),
                'name' => 'Registrar Officer',
                'email' => 'registrar@ub.edu.bz',
                'type' => 'staff',
                'department' => 'Registrar',
                'status' => 'active',
            ]);
        }

        $employee = User::query()->where('email', 'employee@ub.edu.bz')->first();
        if (! $employee) {
            $employee = User::query()->forceCreate([
                'id' => (string) Str::uuid(),
                'name' => 'Example Employee',
                'email' => 'employee@ub.edu.bz',
                'type' => 'staff',
                'department' => 'Finance',
                'status' => 'active',
            ]);
        }

        // 6. Assign users to groups and direct roles.
        $admin->groups()->syncWithoutDetaching([$ictGroup->id]);
        $registrarUser->groups()->syncWithoutDetaching([$registrarOffice->id]);
        $registrarUser->roles()->syncWithoutDetaching([$financeReviewer->id]);

        // 7. Build menu items to show how roles control UI navigation.
        $adminMenu = MenuItem::firstOrCreate([
            'label' => 'Administration',
            'path' => '/admin',
        ], [
            'icon' => 'shield',
            'role_id' => $superAdmin->id,
            'parent_id' => null,
            'sort_order' => 1,
        ]);

        MenuItem::firstOrCreate([
            'label' => 'Audit Logs',
            'path' => '/admin/audit-logs',
        ], [
            'icon' => 'clipboard-list',
            'role_id' => $superAdmin->id,
            'parent_id' => $adminMenu->id,
            'sort_order' => 1,
        ]);

        MenuItem::firstOrCreate([
            'label' => 'Finance Access',
            'path' => '/finance/access-requests',
        ], [
            'icon' => 'briefcase',
            'role_id' => $financeReviewer->id,
            'parent_id' => null,
            'sort_order' => 2,
        ]);

        // 8. Create a sample access request from an employee into the finance app.
        $request = AccessRequest::query()->firstOrCreate([
            'requester_id' => $employee->id,
            'app_id' => $financeApp->id,
            'requested_role_id' => $registrar->id,
        ], [
            'status' => 'pending',
        ]);

        // 9. Record audit events that explain the approval workflow.
        AuditLog::query()->firstOrCreate([
            'actor_id' => $employee->id,
            'target_id' => $employee->id,
            'app_id' => $financeApp->id,
            'action' => 'Submitted access request for Registrar role',
            'severity' => 'low',
        ]);

        AuditLog::query()->firstOrCreate([
            'actor_id' => $admin->id,
            'target_id' => $employee->id,
            'app_id' => $identityApp->id,
            'action' => 'Assigned ICT Department group to System Administrator',
            'severity' => 'medium',
        ]);

        AuditLog::query()->firstOrCreate([
            'actor_id' => $registrarUser->id,
            'target_id' => $employee->id,
            'app_id' => $financeApp->id,
            'action' => 'Reviewed pending finance access request',
            'severity' => 'medium',
        ]);

        // 10. Output summary for anyone reading the seeder source:
        // admin@ub.edu.bz inherits Super Admin through ICT Department.
        // registrar@ub.edu.bz inherits Registrar through Registrar Office and also has a direct Finance Reviewer role.
        // employee@ub.edu.bz demonstrates how an end user submits an access request for a role in an application.
        // Menu items show how roles can drive navigation visibility.
        // Audit logs show how actions in that flow can be captured for traceability.
    }
}
