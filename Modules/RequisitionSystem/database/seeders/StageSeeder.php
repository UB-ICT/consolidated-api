<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\RequisitionSystem\Models\Stage;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        Stage::insert([
            ['name' => 'Submitted', 'pipeline_id' => 1],
            ['name' => 'Director Approval', 'pipeline_id' => 1],
            ['name' => 'Budget Officer', 'pipeline_id' => 1],
            ['name' => 'VP approval', 'pipeline_id' => 1],
            ['name' => 'Finance Approval', 'pipeline_id' => 1],
        ]);
    }
}
