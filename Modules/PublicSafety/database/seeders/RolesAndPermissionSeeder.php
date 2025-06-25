<?php

namespace Modules\PublicSafety\Database\Seeders;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use Illuminate\Database\Seeder;

class RolesAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();


        // Create permissions
        Permission::create(['name' => 'create users', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit users', 'guard_name' => 'web']);
        Permission::create(['name' => 'view users', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete users', 'guard_name' => 'web']);

        // update cache to know about the newly created permissions (required if using WithoutModelEvents in seeders)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

         // Create roles and assign permissions
         $superAdmin = Role::create(['name' => 'Super Admin']);
         $superAdmin->givePermissionTo(Permission::all());
 
         $admin = Role::create(['name' => 'Admin']);
         $admin->givePermissionTo(['create users', 'edit users', 'view users']);
 
         $staff = Role::create(['name' => 'Staff']);
         $staff->givePermissionTo(['view users']);

    }
}
