<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Models\BudgetYear;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Support\BudgetWorkflow;

class BudgetPipelineSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from live budget pipeline + pipeline_stages.
        // User ↔ stage assignments live in UserStageSeeder (exact DB rows).
        $pipeline = Pipeline::firstOrCreate(['name' => BudgetWorkflow::PIPELINE_NAME]);

        $stages = [
            1 => 'Cost Center Draft',
            2 => 'Budget Officer Review',
            3 => 'Senior Accountant Review',
            4 => 'Finance Director Approval',
        ];

        foreach ($stages as $sequence => $name) {
            $stage = Stage::firstOrCreate(['name' => $name]);

            DB::connection('porsql')->table('pipeline_stages')->updateOrInsert(
                [
                    'pipeline_id' => $pipeline->id,
                    'stage_id' => $stage->id,
                ],
                [
                    'sequence' => $sequence,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        BudgetYear::firstOrCreate(
            ['label' => '2025-2026'],
            ['submissions_open' => false]
        );

        BudgetYear::firstOrCreate(
            ['label' => '2026-2027'],
            ['submissions_open' => false]
        );
    }
}
