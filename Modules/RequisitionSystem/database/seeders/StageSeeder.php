<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Stage;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from live stages table (pipeline + standalone + legacy aliases).
        $stages = [
            'Draft',
            "Director's Approval",
            'Budget Officer',
            'VP Approval',
            'Finance Approval',
            'Cost Center Draft',
            'Budget Officer Review',
            'Senior Accountant Review',
            'Finance Director Approval',
            'Purchase Approval',
            // Legacy aliases present in DB (not on active pipelines).
            'Director Approval',
            'VP approval',
        ];

        foreach ($stages as $name) {
            Stage::firstOrCreate(['name' => $name]);
        }
    }
}
