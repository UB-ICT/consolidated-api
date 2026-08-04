<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;

class AuthDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RoleSeeder::class,
            AuthTestDataSeeder::class,
            CostCentersMenuSeeder::class,
            DefaultApplicationSeeder::class,
        ]);
    }
}
