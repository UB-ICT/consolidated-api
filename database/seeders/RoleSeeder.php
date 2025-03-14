<?php

namespace Database\Seeders;

// use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['roles' => 'Super Administrator', 'description' => 'Has full system access', 'guard_name' => 'web'],
            ['roles' => 'Administrator', 'description' => 'Manages system settings', 'guard_name' => 'web'],
            ['roles' => 'Employee', 'description' => 'Limited access for employees', 'guard_name' => 'web'],
            // ['roles' => 'Student', 'description' => 'Limited access for students', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['roles' => $role['roles']], $role);
        }

        $role = Role::find(1);


        $permission = Permission::create(['name' => 'Edit User']);

        $role->givePermissionTo($permission);


        $permission = Permission::create(['name' => 'Delete User']);

        $role->givePermissionTo($permission);


    }
}