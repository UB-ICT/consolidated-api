<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;

class PipelineSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from live operations pipeline + pipeline_stages.
        // User ↔ stage assignments live in UserStageSeeder (exact DB rows).
        $pipeline = Pipeline::firstOrCreate(['name' => RequisitionWorkflow::PIPELINE_NAME]);

        $stages = [
            1 => 'Draft',
            2 => "Director's Approval",
            3 => 'Budget Officer',
            4 => 'VP Approval',
            5 => 'Finance Approval',
            6 => 'Purchase Approval',
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
    }
}
