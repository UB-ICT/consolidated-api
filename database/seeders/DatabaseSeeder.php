<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Message\Database\Seeders\MessageSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            CampusSeeder::class,
            UserStatusSeeder::class,

            UserSeeder::class,
            MessageCategorySeeder::class,
            UserCampusSeeder::class,
            BuildingSeeder::class,
            MessageSeeder::class,
            IncidentFileSeeder::class,
            IncidentStatusSeeder::class,
            IncidentReportSeeder::class,
            AccessRightSeeder::class,
            RecipientSeeder::class,
            IncidentTypeSeeder::class,
            DepartmentSeeder::class,
            DepartmentMemberSeeder::class,
            MenuSeeder::class,
            MenuRoleSeeder::class,
            SubMenuSeeder::class,
        ]);
    }
}