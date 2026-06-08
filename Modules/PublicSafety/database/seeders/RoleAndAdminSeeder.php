<?php

namespace Modules\PublicSafety\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define all legacy group names to match your systems exactly
        $roles = [
            // Public Safety Roles
            'api_public_safety_Admin@ub.edu.bz'    => 'Chief Public Safety Officer and System Administrators',
            'api_public_safety_Security@ub.edu.bz' => 'Shift Supervisors',
            'api_public_safety_Officer@ub.edu.bz'  => 'Public Safety Officers',

            // Annual Report Roles
            'api_annual_report_Developers@ub.edu.bz' => 'Annual Report Developers',
            'api_annual_report_HR@ub.edu.bz'        => 'Annual Report HR Personnel',
            'api_annual_report_Finance@ub.edu.bz'   => 'Annual Report Finance Personnel',
            'api_annual_report_Records@ub.edu.bz'   => 'Annual Report Records Personnel',
            'api_annual_report_Directors@ub.edu.bz' => 'Annual Report Directors',
            'api_annual_report_Admin@ub.edu.bz'     => 'Annual Report Administrators',
            'api_annual_report_Deans@ub.edu.bz'     => 'Annual Report Deans',
        ];

        // 2. Insert roles into the PostgreSQL table if they don't exist yet
        $createdRoles = [];
        foreach ($roles as $name => $description) {
            $createdRoles[$name] = Role::firstOrCreate(
                ['role_name' => $name],
                ['description' => $description]
            );
        }

        // 3. Find your specific user record to grant initial bootstrap privileges
        $adminEmail = 'luis.herrera@ub.edu.bz';
        $user = User::where('email', $adminEmail)->first();

        if ($user) {
            // syncWithoutDetaching links your user UUID to both role UUIDs in user_roles
            $user->roles()->syncWithoutDetaching([
                $createdRoles['api_public_safety_Admin@ub.edu.bz']->id,
                $createdRoles['api_annual_report_Admin@ub.edu.bz']->id,
            ]);
            $this->command->info("Successfully assigned Admin roles to {$adminEmail}!");
        } else {
            $this->command->warn("User {$adminEmail} not found in the 'users' table.");
            $this->command->warn("Please log into the application via Google OAuth once first so your account record is initialized, then re-run this seeder.");
        }
    }
}
