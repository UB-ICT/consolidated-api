<?php

namespace Modules\RequisitionSystem\Tests\Unit\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Support\GuardsRequisitionCancellation;
use Modules\RequisitionSystem\Support\GuardsRequisitionEditing;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;
use Modules\RequisitionSystem\Tests\Support\CreatesRequisitionWorkflowSchema;
use Tests\TestCase;

class RequisitionCancellationGuardTest extends TestCase
{
    use CreatesRequisitionWorkflowSchema;
    use GuardsRequisitionEditing;
    use GuardsRequisitionCancellation {
        userCanCancelRequisition as public;
        userIsCostCenterOrDirectorDean as public;
    }

    protected int $costCenterId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRequisitionWorkflowSchema();
        $this->setUpAuthRoleSchema();
        $this->setUpCostCenterSchema();
    }

    public function test_requester_assigned_to_cost_center_can_cancel_pending_requisition(): void
    {
        [$user, $requisition] = $this->makeCostCenterUserWithRequisition('requester', 'Pending');

        $this->assertTrue($this->userCanCancelRequisition($requisition, $user));
    }

    public function test_director_dean_assigned_to_cost_center_can_cancel_draft_requisition(): void
    {
        [$user, $requisition] = $this->makeCostCenterUserWithRequisition(
            'director-dean',
            'Draft',
            $this->stageIds['Draft'],
            1
        );

        $this->assertTrue($this->userCanCancelRequisition($requisition, $user));
    }

    public function test_user_cannot_cancel_rejected_requisition(): void
    {
        [$user, $requisition] = $this->makeCostCenterUserWithRequisition('requester', 'Rejected');

        $this->assertFalse($this->userCanCancelRequisition($requisition, $user));
    }

    public function test_user_cannot_cancel_approved_requisition(): void
    {
        [$user, $requisition] = $this->makeCostCenterUserWithRequisition(
            'requester',
            'Approved',
            $this->stageIds['Finance Approval'],
            5
        );

        $this->assertFalse($this->userCanCancelRequisition($requisition, $user));
    }

    public function test_user_cannot_cancel_requisition_for_other_cost_center(): void
    {
        [$user, $requisition] = $this->makeCostCenterUserWithRequisition('requester', 'Pending');

        DB::connection('porsql')->table('requisitions')
            ->where('id', $requisition->id)
            ->update(['cost_center_id' => 999]);

        $requisition->refresh();

        $this->assertFalse($this->userCanCancelRequisition($requisition, $user));
    }

    public function test_approver_without_cost_center_role_cannot_cancel(): void
    {
        [, $requisition] = $this->makeCostCenterUserWithRequisition('requester', 'Pending');

        $approver = new User([
            'name'   => 'Budget Officer',
            'email'  => 'budget.officer@ub.edu.bz',
            'status' => 'active',
        ]);
        $approver->id = '77777777-7777-7777-7777-777777777777';
        $approver->exists = true;

        $this->assertFalse($this->userCanCancelRequisition($requisition, $approver));
    }

    public function test_apply_cancellation_sets_cancelled_status_without_changing_stage(): void
    {
        $requisitionId = $this->createTestRequisitionWithCostCenter(
            $this->stageIds['Budget Officer'],
            $this->statusIds['Pending'],
            3,
            $this->costCenterId
        );

        $requisition = Requisition::findOrFail($requisitionId);

        RequisitionWorkflow::applyCancellation($requisition);

        $requisition->refresh();

        $this->assertSame($this->statusIds['Cancelled'], $requisition->status_id);
        $this->assertSame($this->stageIds['Budget Officer'], $requisition->stage_id);
        $this->assertSame(3, $requisition->current_stage_sequence);
    }

    public function test_director_dean_can_cancel_after_requisition_passed_their_stage(): void
    {
        [$user, $requisition] = $this->makeCostCenterUserWithRequisition(
            'director-dean',
            'Pending',
            $this->stageIds['Budget Officer'],
            3
        );

        $this->assertTrue($this->userCanCancelRequisition($requisition, $user));
    }

    public function test_requester_can_cancel_during_cost_center_review(): void
    {
        [$user, $requisition] = $this->makeCostCenterUserWithRequisition(
            'requester',
            'Cost Center Review',
            $this->stageIds['Budget Officer'],
            3
        );

        $this->assertTrue($this->userCanCancelRequisition($requisition, $user));
    }

    /**
     * @return array{0: User, 1: Requisition}
     */
    private function makeCostCenterUserWithRequisition(
        string $roleName,
        string $statusName,
        ?int $stageId = null,
        ?int $sequence = null
    ): array {
        $roleId = (string) Str::uuid();

        DB::connection('pgsql')->table('roles')->insert([
            'id'          => $roleId,
            'role_name'   => $roleName,
            'description' => ucfirst(str_replace('-', ' ', $roleName)),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $user = new User([
            'name'   => 'Cost Center User',
            'email'  => "{$roleName}@ub.edu.bz",
            'status' => 'active',
        ]);
        $user->id = (string) Str::uuid();
        $user->exists = true;

        DB::connection('pgsql')->table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);

        DB::connection('porsql')->table('user_cost_center')->insert([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenterId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $stageId ??= $this->stageIds["Director's Approval"];
        $sequence ??= 2;

        $requisitionId = $this->createTestRequisitionWithCostCenter(
            $stageId,
            $this->statusIds[$statusName],
            $sequence,
            $this->costCenterId
        );

        $requisition = Requisition::findOrFail($requisitionId);
        $requisition->setRelation('status', (object) [
            'id'   => $this->statusIds[$statusName],
            'name' => $statusName,
        ]);

        return [$user, $requisition];
    }

    private function createTestRequisitionWithCostCenter(
        int $stageId,
        int $statusId,
        int $sequence,
        int $costCenterId
    ): int {
        return DB::connection('porsql')->table('requisitions')->insertGetId([
            'stage_id'               => $stageId,
            'status_id'              => $statusId,
            'current_stage_sequence' => $sequence,
            'cost_center_id'         => $costCenterId,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
    }

    private function setUpAuthRoleSchema(): void
    {
        Schema::connection('pgsql')->dropIfExists('user_roles');
        Schema::connection('pgsql')->dropIfExists('roles');

        Schema::connection('pgsql')->create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role_name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('user_roles', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('role_id');
        });

        Role::unguard();
    }

    private function setUpCostCenterSchema(): void
    {
        Schema::connection('porsql')->dropIfExists('user_cost_center');
        Schema::connection('porsql')->dropIfExists('cost_centers');

        Schema::connection('porsql')->table('requisitions', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_center_id')->nullable();
        });

        Schema::connection('porsql')->create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('porsql')->create('user_cost_center', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->timestamps();
        });

        $this->costCenterId = DB::connection('porsql')->table('cost_centers')->insertGetId([
            'name'       => 'ICT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
