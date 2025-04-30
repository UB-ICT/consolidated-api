<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Campus;
use App\Models\UserStatus;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create required relationships first with proper guard_name
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        $facultyRole = Role::firstOrCreate(
            ['name' => 'faculty'],
            ['guard_name' => 'web', ]
        );

        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['guard_name' => 'web',]
        );

        $staffRole = Role::firstOrCreate(
            ['name' => 'staff'],
            ['guard_name' => 'web', ]
        );

        $activeStatus = UserStatus::firstOrCreate(
            ['name' => 'active'],
        );

        // Ensure campus with ID 2 exists
        $campus = Campus::find(2);
        if (!$campus) {
            $campus = Campus::create([
                'id' => Str::uuid(),
                'campus' => 'Social Studies Campus'
            ]);
            Log::warning('Campus with ID 2 did not exist. Created new campus.');
        }

        // Create or update admin user
        $adminUser = User::updateOrCreate(
            ['email' => 'james.faber@ub.edu.bz'],
            [
                'id' => Str::uuid(),
                'name' => 'James Faber',
                'email' => 'james.faber@ub.edu.bz',
                'domain' => 'ub.edu.bz',
                'password' => Hash::make('Kingjames_x2'),
                'role_id' => $adminRole->id,
                'campus_id' => $campus->id,
                // 'user_status_id' => $activeStatus->id,
                'user_status_id' => 1,

                'email_verified_at' => now(),
                'guid' => Str::uuid(),
            ]
        );

        // Assign role using Spatie permission if package is installed
        if (method_exists($adminUser, 'assignRole')) {
            $adminUser->assignRole($adminRole);
        }

        // Create additional sample users
        $this->createSampleUsers($facultyRole, $studentRole, $staffRole, $activeStatus);
    }

    protected function createSampleUsers($facultyRole, $studentRole, $staffRole, $activeStatus): void
    {
        $campuses = Campus::all();

        if ($campuses->isEmpty()) {
            $campuses = collect([
                Campus::create([
                    'id' => Str::uuid(),
                    'campus' => 'Default Campus'
                ])
            ]);
            Log::warning('No campuses found. Created a default campus.');
        }

        $users = [
            [
                'name' => 'Faculty Member',
                'email' => 'faculty@ub.edu.bz',
                'role_id' => $facultyRole->id,
                'role_to_assign' => $facultyRole,
                'campus_id' => $campuses->first()->id,
                'user_status_id' => $activeStatus->id
            ],
            [
                'name' => 'Student User',
                'email' => 'student@ub.edu.bz',
                'role_id' => $studentRole->id,
                'role_to_assign' => $studentRole,
                'campus_id' => $campuses->last()->id,
                'user_status_id' => $activeStatus->id
            ],
            [
                'name' => 'Staff Member',
                'email' => 'staff@ub.edu.bz',
                'role_id' => $staffRole->id,
                'role_to_assign' => $staffRole,
                'campus_id' => $campuses->first()->id,
                'user_status_id' => $activeStatus->id
            ],
        ];

        foreach ($users as $userData) {
            $roleToAssign = $userData['role_to_assign'];
            unset($userData['role_to_assign']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'id' => Str::uuid(),
                    'domain' => 'ub.edu.bz',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'guid' => Str::uuid(),
                ])
            );

            if (method_exists($user, 'assignRole')) {
                $user->assignRole($roleToAssign);
            }
        }
    }
}