<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\RequisitionSystem\Models\Status;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        Status::insert([
            ['name' => 'Draft'],
            ['name' => 'Pending'],
            ['name' => 'Approved'],
            ['name' => 'Rejected'],
            ['name' => 'Under Review'],
        ]);
    }
}
