<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $db = DB::connection($this->connection);

        $operationsPipelineId = $db->table('pipelines')
            ->where('name', 'operations')
            ->value('id');

        if (!$operationsPipelineId) {
            return;
        }

        $purchaseStageIds = $db->table('stages')
            ->whereIn('name', ['Purchase Approval', 'Purchase Officer'])
            ->pluck('id');

        if ($purchaseStageIds->isEmpty()) {
            return;
        }

        $db->table('pipeline_stages')
            ->where('pipeline_id', $operationsPipelineId)
            ->whereIn('stage_id', $purchaseStageIds)
            ->delete();
    }

    public function down(): void
    {
        $db = DB::connection($this->connection);

        $operationsPipelineId = $db->table('pipelines')
            ->where('name', 'operations')
            ->value('id');

        $purchaseStageId = $db->table('stages')
            ->where('name', 'Purchase Approval')
            ->value('id');

        if (!$operationsPipelineId || !$purchaseStageId) {
            return;
        }

        $exists = $db->table('pipeline_stages')
            ->where('pipeline_id', $operationsPipelineId)
            ->where('stage_id', $purchaseStageId)
            ->exists();

        if ($exists) {
            return;
        }

        $db->table('pipeline_stages')->insert([
            'pipeline_id' => $operationsPipelineId,
            'stage_id' => $purchaseStageId,
            'sequence' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
