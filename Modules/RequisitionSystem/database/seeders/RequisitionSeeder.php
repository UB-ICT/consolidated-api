<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\RequisitionSystem\Models\Requisition;

class RequisitionSeeder extends Seeder
{
    public function run(): void
    {
        Requisition::insert([
            [
                'number' => 'REQ-0001',
                'cost_center_id' => 1,
                'supplier_id' => 1,
                'status_id' => 1,
                'currency_id' => 1,
                'conversion_rate_id' => 1,
                'total' => 100,
                'stage_id' => 1,
                'date_prepared' => now(),
            ],
        ]);
    }
}
