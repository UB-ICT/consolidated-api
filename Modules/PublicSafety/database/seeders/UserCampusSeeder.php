<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\UserCampus;
use Illuminate\Database\Seeder;

class UserCampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserCampus::create([
            'user_id' => 1,
            'campus_id' => 1,
            'primary_campus' => true,
        ]);
        
    }
}
