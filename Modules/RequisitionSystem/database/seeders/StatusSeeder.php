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
            ['id' => 5, 'name' => 'Under Review'],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(['id' => $status['id']], $status);
        }
    }
}
