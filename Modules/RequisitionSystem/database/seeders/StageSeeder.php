<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Stage;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from live stages used by operations + budget pipelines.
        $stages = [
            'Draft',
            "Director's Approval",
            'Budget Officer',
            'VP Approval',
            'Finance Approval',
            'Purchase Approval',
            'Cost Center Draft',
            'Budget Officer Review',
            'Senior Accountant Review',
            'Finance Director Approval',
        ];

        foreach ($stages as $name) {
            Stage::firstOrCreate(['name' => $name]);
        }
    }
}
