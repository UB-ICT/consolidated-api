<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Stage;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            'Draft',
            'Director Approval',
            'Budget Officer',
            'VP approval',
            'Finance Approval',
        ];

        foreach ($stages as $name) {
            Stage::firstOrCreate(['name' => $name]);
        }
    }
}
