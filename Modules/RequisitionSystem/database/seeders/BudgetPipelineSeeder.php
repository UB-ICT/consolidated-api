<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\BudgetYear;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\UserStage;
use Modules\RequisitionSystem\Support\BudgetWorkflow;

class BudgetPipelineSeeder extends Seeder
{
    public function run(): void
    {
        $pipeline = Pipeline::firstOrCreate(['name' => BudgetWorkflow::PIPELINE_NAME]);

        $stages = [
            1 => 'Cost Center Draft',
            2 => 'Budget Officer Review',
            3 => 'Senior Accountant Review',
            4 => 'Finance Director Approval',
        ];

        $stageIds = [];

        foreach ($stages as $sequence => $name) {
            $stage = Stage::firstOrCreate(['name' => $name]);
            $stageIds[$sequence] = $stage->id;

            DB::connection('porsql')->table('pipeline_stages')->updateOrInsert(
                [
                    'pipeline_id' => $pipeline->id,
                    'stage_id'    => $stage->id,
                ],
                [
                    'sequence'   => $sequence,
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

        $roleStageMap = [
            'budget-officer'      => 2,
            'senior-account'      => 3,
            'director-of-finance' => 4,
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
                    'user_id'  => $userId,
                    'stage_id' => $stageIds[$sequence],
                ]);
            }
        }

        // Ensure known finance users from the full flow seeder are assigned if present.
        $namedAssignments = [
            'stephanie.palacio@ub.edu.bz' => 2,
            'daren.young@ub.edu.bz' => 4,
        ];

        foreach ($namedAssignments as $email => $sequence) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                continue;
            }

            UserStage::firstOrCreate([
                'user_id'  => $user->id,
                'stage_id' => $stageIds[$sequence],
            ]);
        }
    }
}
