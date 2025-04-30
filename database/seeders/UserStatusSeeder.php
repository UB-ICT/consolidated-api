<?php

namespace Database\Seeders;

use App\Models\UserStatus;
use Illuminate\Database\Seeder;

class UserStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserStatus::create([
            'name' => 'Active',
            'description' => 'Active user account',
            'userStatuses' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ], );
        UserStatus::create([
            'name' => 'Active',
            'description' => 'Active user account',
            'userStatuses' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ], );
        UserStatus::create([
            'name' => 'Suspended',
            'description' => 'Suspended user account',
            'userStatuses' => 'suspended',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
