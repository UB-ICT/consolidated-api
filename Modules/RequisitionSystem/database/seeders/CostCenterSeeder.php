<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Modules\RequisitionSystem\Models\CostCenter::create([
            'name' => 'ICT Department',
            'type' => 'Operational',
        ]);
    }
}
