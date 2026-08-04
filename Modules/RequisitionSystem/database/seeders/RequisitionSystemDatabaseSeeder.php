<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;

class RequisitionSystemDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            StatusSeeder::class,
            StageSeeder::class,
            CountrySeeder::class,
            CurrencySeeder::class,
            BankSeeder::class,
            SettingSeeder::class,
            ChartOfAccountSeeder::class,
            SupplierSeeder::class,
            CostCenterAndDirectorSeeder::class,
            AccountsUsersSeeder::class,
            TagSeeder::class,
            PipelineSeeder::class,
            BudgetPipelineSeeder::class,
            UserStageSeeder::class,
            IctBudgetProjectionSeeder::class,
        ]);
    }
}
