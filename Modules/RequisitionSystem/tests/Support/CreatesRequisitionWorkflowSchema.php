<?php

namespace Modules\RequisitionSystem\Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait CreatesRequisitionWorkflowSchema
{
    protected int $pipelineId = 1;

    /** @var array<string, int> */
    protected array $stageIds = [];

    /** @var array<string, int> */
    protected array $statusIds = [];

    protected function setUpRequisitionWorkflowSchema(): void
    {
        Schema::connection('porsql')->dropIfExists('user_stages');
        Schema::connection('porsql')->dropIfExists('approvals');
        Schema::connection('porsql')->dropIfExists('requisitions');
        Schema::connection('porsql')->dropIfExists('pipeline_stages');
        Schema::connection('porsql')->dropIfExists('pipelines');
        Schema::connection('porsql')->dropIfExists('stages');
        Schema::connection('porsql')->dropIfExists('statuses');

        Schema::connection('porsql')->create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('porsql')->create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('porsql')->create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('porsql')->create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('pipelines');
            $table->foreignId('stage_id')->constrained('stages');
            $table->unsignedInteger('sequence');
            $table->timestamps();
        });

        Schema::connection('porsql')->create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->nullable()->constrained('pipelines');
            $table->unsignedBigInteger('stage_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('reviewing_cost_center_id')->nullable();
            $table->unsignedInteger('current_stage_sequence')->nullable();
            $table->timestamps();
        });

        Schema::connection('porsql')->create('user_stages', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreignId('stage_id')->constrained('stages');
            $table->timestamps();
        });

        Schema::connection('porsql')->create('approvals', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreignId('requisition_id')->constrained('requisitions');
            $table->foreignId('stage_id')->constrained('stages');
            $table->string('status');
            $table->text('comments')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        $this->seedWorkflowStatuses();
        $this->seedWorkflowPipeline();
    }

    protected function seedWorkflowStatuses(): void
    {
        foreach (['Draft', 'Pending', 'Approved', 'Rejected', 'Cost Center Review', 'Cancelled', 'Closed'] as $name) {
            $id = DB::connection('porsql')->table('statuses')->insertGetId([
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->statusIds[$name] = $id;
        }
    }

    protected function seedWorkflowPipeline(): void
    {
        $this->pipelineId = DB::connection('porsql')->table('pipelines')->insertGetId([
            'name'       => 'operations',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stageNames = [
            1 => 'Draft',
            2 => "Director's Approval",
            3 => 'Budget Officer',
            4 => 'VP Approval',
            5 => 'Finance Approval',
        ];

        foreach ($stageNames as $sequence => $name) {
            $stageId = DB::connection('porsql')->table('stages')->insertGetId([
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->stageIds[$name] = $stageId;

            DB::connection('porsql')->table('pipeline_stages')->insert([
                'pipeline_id' => $this->pipelineId,
                'stage_id'    => $stageId,
                'sequence'    => $sequence,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    protected function createTestRequisition(int $stageId, int $statusId, int $sequence): int
    {
        return DB::connection('porsql')->table('requisitions')->insertGetId([
            'pipeline_id'            => $this->pipelineId,
            'stage_id'               => $stageId,
            'status_id'              => $statusId,
            'current_stage_sequence' => $sequence,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
    }
}
