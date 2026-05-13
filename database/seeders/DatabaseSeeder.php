<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
<<<<<<< HEAD
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\RequisitionSystem\Database\Seeders\RequisitionSystemDatabaseSeeder;
use Modules\UBForms\Database\Seeders\UBFormsDatabaseSeeder;
use Modules\Xenegrade\Database\Seeders\XenegradeDatabaseSeeder;
use Modules\UBPortal\Database\Seeders\UBPortalDatabaseSeeder;
=======
>>>>>>> dev

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
<<<<<<< HEAD
            AuthDatabaseSeeder::class,
            RequisitionSystemDatabaseSeeder::class,
            UBFormsDatabaseSeeder::class,
            UBPortalDatabaseSeeder::class,
            XenegradeDatabaseSeeder::class,
=======
            \Modules\PublicSafety\Database\Seeders\DatabaseSeeder::class,
>>>>>>> dev
        ]);
    }
}
