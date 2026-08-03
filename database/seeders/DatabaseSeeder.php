<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            \Modules\Auth\Database\Seeders\AuthDatabaseSeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\StageSeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\CountrySeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\CurrencySeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\StatusSeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\BankSeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\SupplierSeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\StatusSeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\StageSeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\CostCenterAndDirectorSeeder::class,
            \Modules\RequisitionSystem\Database\Seeders\AccountsUsersSeeder::class,
        ]);
    }
}
