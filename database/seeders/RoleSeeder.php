<?php

namespace Database\Seeders;

// use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['roles' => 'super admin', 'description' => 'Has full system access', 'guard_name' => 'web'],
            ['roles' => 'admin', 'description' => 'Manages system settings', 'guard_name' => 'web'],
            ['roles' => 'employee', 'description' => 'Limited access for employees', 'guard_name' => 'web'],
            ['roles' => 'student', 'description' => 'Limited access for students', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['roles' => $role['roles']], $role);
        }
    }
}