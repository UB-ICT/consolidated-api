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
        // Synced from intended operations pipeline (no purchase stage).
        // Purchase officers act after Finance Approval sets status to Approved.
        // User ↔ stage assignments live in UserStageSeeder.
        $pipeline = Pipeline::firstOrCreate(['name' => RequisitionWorkflow::PIPELINE_NAME]);

        $stages = [
            1 => 'Draft',
            2 => "Director's Approval",
            3 => 'Budget Officer',
            4 => 'VP Approval',
            5 => 'Finance Approval',
        ];

        $keptStageIds = [];

        foreach ($stages as $sequence => $name) {
            $stage = Stage::firstOrCreate(['name' => $name]);
            $keptStageIds[] = $stage->id;

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

        // Drop purchase (or any other) stages that should not sit on operations.
        DB::connection('porsql')->table('pipeline_stages')
            ->where('pipeline_id', $pipeline->id)
            ->whereNotIn('stage_id', $keptStageIds)
            ->delete();
    }
}
