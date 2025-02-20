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
            ['name' => 'super admin', 'description' => 'Has full system access', 'guard_name' => 'web'],
            ['name' => 'admin', 'description' => 'Manages system settings', 'guard_name' => 'web'],
            ['name' => 'employee', 'description' => 'Limited access for employees', 'guard_name' => 'web'],
            ['name' => 'student', 'description' => 'Limited access for students', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}