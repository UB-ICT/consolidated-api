<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\UserStatus;
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
        UserStatus::create(['userStatuses' => 'Active']);
        UserStatus::create(['userStatuses' => 'Inactive']);
        UserStatus::create(['userStatuses' => 'Suspended']);   
     }
}
