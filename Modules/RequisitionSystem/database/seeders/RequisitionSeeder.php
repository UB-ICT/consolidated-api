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
                'status_id' => 1,
                'currency_id' => 1,
                'total' => 100,
                'stage_id' => 1,
                'priority' => 'routine',
                'expected_delivery_date' => now()->addWeek()->format('Y-m-d'),
                'is_recurring' => false,
                'reminder_date' => null,
                'date_prepared' => now(),
            ],
        ]);
    }
}
