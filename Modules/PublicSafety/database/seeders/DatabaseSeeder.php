<?php

namespace Modules\PublicSafety\Database\Seeders;

use Illuminate\Database\Seeder;

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
            IncidentStatusSeeder::class,
            IncidentTypeSeeder::class,
            IncidentReportSeeder::class,
            AccessRightSeeder::class,
            DepartmentSeeder::class,
            DepartmentMemberSeeder::class,
            MenuSeeder::class,
            MenuRoleSeeder::class,
            SubMenuSeeder::class,
        ]);
    }
}