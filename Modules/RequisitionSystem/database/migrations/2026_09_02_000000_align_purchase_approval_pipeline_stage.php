<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Align the operations pipeline last stage with user_stages assignments.
 *
 * Production had "Purchase Officer Approval" on the pipeline while approvers
 * are assigned to the standalone "Purchase Approval" stage — requisitions
 * advanced to the wrong stage_id and last-stage users could not view them.
 */
return new class extends Migration
{
    protected $connection = 'porsql';

    /** @var list<string> */
    private const PURCHASE_STAGE_NAMES = [
        'Purchase Approval',
        'Purchase Officer Approval',
        'Purchase Officer',
    ];

    public function up(): void
    {
        $db = DB::connection($this->connection);

        $pipelineId = $db->table('pipelines')
            ->where('name', 'operations')
            ->value('id');

        if (!$pipelineId) {
            return;
        }

        $canonicalName = 'Purchase Approval';
        $canonicalStageId = $db->table('stages')
            ->where('name', $canonicalName)
            ->value('id');

        if (!$canonicalStageId) {
            $canonicalStageId = $db->table('stages')->insertGetId([
                'name'       => $canonicalName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $aliasStageIds = $db->table('stages')
            ->whereIn('name', self::PURCHASE_STAGE_NAMES)
            ->where('id', '!=', $canonicalStageId)
            ->pluck('id');

        foreach ($aliasStageIds as $aliasStageId) {
            $db->table('requisitions')
                ->where('stage_id', $aliasStageId)
                ->update(['stage_id' => $canonicalStageId]);

            $duplicateApprovalIds = $db->table('approvals as alias')
                ->join('approvals as canonical', function ($join) use ($canonicalStageId) {
                    $join->on('alias.user_id', '=', 'canonical.user_id')
                        ->on('alias.requisition_id', '=', 'canonical.requisition_id')
                        ->where('canonical.stage_id', '=', $canonicalStageId);
                })
                ->where('alias.stage_id', $aliasStageId)
                ->pluck('alias.id');

            if ($duplicateApprovalIds->isNotEmpty()) {
                $db->table('approvals')
                    ->whereIn('id', $duplicateApprovalIds)
                    ->delete();
            }

            $db->table('approvals')
                ->where('stage_id', $aliasStageId)
                ->update(['stage_id' => $canonicalStageId]);

            // Some users already have the canonical stage — drop alias rows instead
            // of updating them and violating user_stages_user_id_stage_id_unique.
            $usersWithCanonicalStage = $db->table('user_stages')
                ->where('stage_id', $canonicalStageId)
                ->pluck('user_id');

            if ($usersWithCanonicalStage->isNotEmpty()) {
                $db->table('user_stages')
                    ->where('stage_id', $aliasStageId)
                    ->whereIn('user_id', $usersWithCanonicalStage)
                    ->delete();
            }

            $db->table('user_stages')
                ->where('stage_id', $aliasStageId)
                ->update(['stage_id' => $canonicalStageId]);

            $db->table('pipeline_stages')
                ->where('pipeline_id', $pipelineId)
                ->where('stage_id', $aliasStageId)
                ->delete();
        }

        $db->table('pipeline_stages')->updateOrInsert(
            [
                'pipeline_id' => $pipelineId,
                'stage_id'    => $canonicalStageId,
            ],
            [
                'sequence'   => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $maxSequence = (int) $db->table('pipeline_stages')
            ->where('pipeline_id', $pipelineId)
            ->max('sequence');

        $db->table('requisitions')
            ->where('pipeline_id', $pipelineId)
            ->where('stage_id', $canonicalStageId)
            ->where(function ($query) use ($maxSequence) {
                $query->whereNull('current_stage_sequence')
                    ->orWhere('current_stage_sequence', '<', $maxSequence);
            })
            ->update(['current_stage_sequence' => $maxSequence]);
    }

    public function down(): void
    {
        // Data repair migration — no safe automatic rollback.
    }
};
