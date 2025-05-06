<?php

namespace Modules\PublicSafety\Database\Seeders;

use Illuminate\Database\Seeder;

class PublicSafetyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionSeeder::class,
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
