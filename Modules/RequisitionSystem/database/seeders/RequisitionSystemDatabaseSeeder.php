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
            CostCenterSeeder::class,
            UserSeeder::class,
            StatusSeeder::class,
            PipelineSeeder::class,
            StageSeeder::class,
            UserStageSeeder::class,
            CurrencySeeder::class,
            ConversionRateSeeder::class,
            BankSeeder::class,
            SupplierSeeder::class,
            SupplierBankSeeder::class,
            CountrySeeder::class,
            AddressSeeder::class,
            RequisitionSeeder::class,
            ItemSeeder::class,
            AttachmentSeeder::class,
            ApprovalSeeder::class,
            FullRequisitionFlowSeeder::class,
        ]);
    }
}
