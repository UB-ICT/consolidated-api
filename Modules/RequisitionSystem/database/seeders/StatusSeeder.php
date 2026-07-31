<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Status;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'Draft'],
            ['id' => 2, 'name' => 'Pending'],
            ['id' => 3, 'name' => 'Approved'],
            ['id' => 4, 'name' => 'Rejected'],
            ['id' => 6, 'name' => 'Cost Center Review'],
            ['id' => 7, 'name' => 'Deleted'],
            ['id' => 8, 'name' => 'Cancelled'],
            ['id' => 9, 'name' => 'Closed'],
            ['id' => 10, 'name' => 'Active'],
            ['id' => 11, 'name' => 'Inactive'],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(['id' => $status['id']], $status);
        }
    }
}
