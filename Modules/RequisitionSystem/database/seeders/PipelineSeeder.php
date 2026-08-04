<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\Role;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\UserStage;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;

class PipelineSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from live operations pipeline + pipeline_stages.
        $pipeline = Pipeline::firstOrCreate(['name' => RequisitionWorkflow::PIPELINE_NAME]);

        $stages = [
            1 => 'Draft',
            2 => "Director's Approval",
            3 => 'Budget Officer',
            4 => 'VP Approval',
            5 => 'Finance Approval',
            6 => 'Purchase Approval',
        ];

        $stageIds = [];

        foreach ($stages as $sequence => $name) {
            $stage = Stage::firstOrCreate(['name' => $name]);
            $stageIds[$sequence] = $stage->id;

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

        $roleStageMap = [
            'director-dean' => 2,
            'budget-officer' => 3,
            'vice-president' => 4,
            'director-of-finance' => 5,
            'purchase-officer' => 6,
        ];

        foreach ($roleStageMap as $roleName => $sequence) {
            $role = Role::where('role_name', $roleName)->first();

            if (!$role) {
                continue;
            }

            $userIds = DB::connection('pgsql')
                ->table('user_roles')
                ->where('role_id', $role->id)
                ->pluck('user_id');

            foreach ($userIds as $userId) {
                UserStage::firstOrCreate([
                    'user_id' => $userId,
                    'stage_id' => $stageIds[$sequence],
                ]);
            }
        }

        // President acts at the director approval stage for Board/President CCs.
        $presidentRole = Role::where('role_name', 'president')->first();

        if ($presidentRole) {
            $userIds = DB::connection('pgsql')
                ->table('user_roles')
                ->where('role_id', $presidentRole->id)
                ->pluck('user_id');

            foreach ($userIds as $userId) {
                UserStage::firstOrCreate([
                    'user_id' => $userId,
                    'stage_id' => $stageIds[2],
                ]);
            }
        }
    }
}
