<?php

namespace Modules\RequisitionSystem\Tests\Unit\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Approval;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\UserStage;
use Modules\RequisitionSystem\Support\GuardsRequisitionApproval;
use Modules\RequisitionSystem\Tests\Support\CreatesRequisitionWorkflowSchema;

use Tests\TestCase;

class RequisitionApprovalGuardTest extends TestCase
{
    use CreatesRequisitionWorkflowSchema;
    use GuardsRequisitionApproval {
        findUserStageDecision as public;
        canUserApproveRequisition as public;
        userCanViewApprovalActions as public;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Run your existing workflow schema builder (which now sets up both porsql and pgsql)
        $this->setUpRequisitionWorkflowSchema();
    }

    public function test_user_can_view_approval_actions_when_assigned_to_current_stage(): void
    {
        [$user, $requisition] = $this->makeAssignedRequisitionAtDirectorStage();

        $this->assertTrue($this->userCanViewApprovalActions($requisition, $user));
    }

    public function test_user_can_approve_when_pending_and_no_prior_decision(): void
    {
        [$user, $requisition] = $this->makeAssignedRequisitionAtDirectorStage();

        $this->assertTrue($this->canUserApproveRequisition($requisition, $user));
        $this->assertNull($this->findUserStageDecision($requisition, $user));
    }

    public function test_user_cannot_approve_after_recording_approval_at_stage(): void
    {
        [$user, $requisition] = $this->makeAssignedRequisitionAtDirectorStage();

        Approval::create([
            'requisition_id' => $requisition->id,
            'user_id'        => $user->id,
            'stage_id'       => $this->stageIds["Director's Approval"],
            'status'         => 'approved',
            'signed_at'      => now(),
        ]);

        $requisition->refresh();

        $this->assertFalse($this->canUserApproveRequisition($requisition, $user));
        $this->assertSame(
            'approved',
            $this->findUserStageDecision($requisition, $user)?->status
        );
        $this->assertTrue($this->userCanViewApprovalActions($requisition, $user));
    }

    public function test_user_cannot_approve_after_recording_rejection_at_stage(): void
    {
        [$user, $requisition] = $this->makeAssignedRequisitionAtDirectorStage(
            $this->statusIds['Rejected']
        );

        Approval::create([
            'requisition_id' => $requisition->id,
            'user_id'        => $user->id,
            'stage_id'       => $this->stageIds["Director's Approval"],
            'status'         => 'rejected',
            'signed_at'      => now(),
        ]);

        $requisition->refresh();

        $this->assertFalse($this->canUserApproveRequisition($requisition, $user));
        $this->assertSame(
            'rejected',
            $this->findUserStageDecision($requisition, $user)?->status
        );
    }

    public function test_user_cannot_approve_after_requesting_cost_center_review_at_stage(): void
    {
        [$user, $requisition] = $this->makeAssignedRequisitionAtDirectorStage(
            $this->statusIds['Cost Center Review']
        );

        Approval::create([
            'requisition_id' => $requisition->id,
            'user_id'        => $user->id,
            'stage_id'       => $this->stageIds["Director's Approval"],
            'status'         => 'cost_center_review',
            'signed_at'      => now(),
        ]);

        $requisition->refresh();

        $this->assertFalse($this->canUserApproveRequisition($requisition, $user));
        $this->assertSame(
            'cost_center_review',
            $this->findUserStageDecision($requisition, $user)?->status
        );
    }

    public function test_user_cannot_view_approval_actions_when_requisition_is_rejected(): void
    {
        [$user, $requisition] = $this->makeAssignedRequisitionAtDirectorStage(
            $this->statusIds['Rejected']
        );

        $this->assertFalse($this->userCanViewApprovalActions($requisition, $user));
    }

    /**
     * @return array{0: User, 1: Requisition}
     */
    private function makeAssignedRequisitionAtDirectorStage(?int $statusId = null): array
    {
        $user = new User(['name' => 'Director', 'email' => 'director@ub.edu.bz']);
        $user->id = '44444444-4444-4444-4444-444444444444';
        $user->exists = true;

        // 🔒 Seed the required role structure into your pgsql schema context for the guard checks
        $roleId = (string) Str::uuid();
        DB::connection('pgsql')->table('roles')->insert([
            'id' => $roleId,
            'role_name' => 'director-dean',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('pgsql')->table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);

        UserStage::create([
            'user_id'  => $user->id,
            'stage_id' => $this->stageIds["Director's Approval"],
        ]);

        $requisitionId = $this->createTestRequisition(
            $this->stageIds["Director's Approval"],
            $statusId ?? $this->statusIds['Pending'],
            2
        );

        $requisition = Requisition::findOrFail($requisitionId);
        $requisition->setRelation('status', (object) [
            'id'   => $statusId ?? $this->statusIds['Pending'],
            'name' => match ($statusId) {
                $this->statusIds['Rejected'] => 'Rejected',
                $this->statusIds['Cost Center Review'] => 'Cost Center Review',
                default => 'Pending',
            },
        ]);

        return [$user, $requisition];
    }
}
