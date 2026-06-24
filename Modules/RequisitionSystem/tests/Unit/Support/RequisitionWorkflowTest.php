<?php

namespace Modules\RequisitionSystem\Tests\Unit\Support;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\UserStage;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;
use Modules\RequisitionSystem\Tests\Support\CreatesRequisitionWorkflowSchema;
use Tests\TestCase;

class RequisitionWorkflowTest extends TestCase
{
    use CreatesRequisitionWorkflowSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRequisitionWorkflowSchema();
    }

    public function test_stage_id_for_sequence_returns_ordered_pipeline_stages(): void
    {
        $draftStageId = RequisitionWorkflow::stageIdForSequence(1, $this->pipelineId);
        $directorStageId = RequisitionWorkflow::stageIdForSequence(2, $this->pipelineId);
        $financeStageId = RequisitionWorkflow::stageIdForSequence(5, $this->pipelineId);

        $this->assertSame($this->stageIds['Draft'], $draftStageId);
        $this->assertSame($this->stageIds["Director's Approval"], $directorStageId);
        $this->assertSame($this->stageIds['Finance Approval'], $financeStageId);
    }

    public function test_next_stage_id_for_current_stage_follows_sequence(): void
    {
        $directorStageId = $this->stageIds["Director's Approval"];
        $budgetStageId = $this->stageIds['Budget Officer'];

        $nextStageId = RequisitionWorkflow::nextStageIdForCurrentStage(
            $directorStageId,
            $this->pipelineId
        );

        $this->assertSame($budgetStageId, $nextStageId);
    }

    public function test_is_last_pipeline_stage(): void
    {
        $this->assertFalse(RequisitionWorkflow::isLastPipelineStage(
            $this->stageIds["Director's Approval"],
            $this->pipelineId
        ));

        $this->assertTrue(RequisitionWorkflow::isLastPipelineStage(
            $this->stageIds['Finance Approval'],
            $this->pipelineId
        ));
    }

    public function test_apply_draft_state_sets_draft_stage_and_status(): void
    {
        $data = [];

        RequisitionWorkflow::applyDraftState($data, $this->pipelineId);

        $this->assertSame($this->statusIds['Draft'], $data['status_id']);
        $this->assertSame($this->stageIds['Draft'], $data['stage_id']);
        $this->assertSame(RequisitionWorkflow::DRAFT_STAGE_SEQUENCE, $data['current_stage_sequence']);
    }

    public function test_apply_submit_state_sets_pending_and_first_approval_stage(): void
    {
        $data = [];

        RequisitionWorkflow::applySubmitState($data, $this->pipelineId);

        $this->assertSame($this->statusIds['Pending'], $data['status_id']);
        $this->assertSame($this->stageIds["Director's Approval"], $data['stage_id']);
        $this->assertSame(RequisitionWorkflow::SUBMITTED_STAGE_SEQUENCE, $data['current_stage_sequence']);
    }

    public function test_apply_resubmit_from_cost_center_review_preserves_stage(): void
    {
        $requisition = new Requisition([
            'stage_id'               => $this->stageIds['Budget Officer'],
            'status_id'              => $this->statusIds['Cost Center Review'],
            'current_stage_sequence' => 3,
        ]);

        $data = [];

        RequisitionWorkflow::applyResubmitFromCostCenterReview($data, $requisition);

        $this->assertSame($this->statusIds['Pending'], $data['status_id']);
        $this->assertSame($this->stageIds['Budget Officer'], $data['stage_id']);
        $this->assertSame(3, $data['current_stage_sequence']);
    }

    public function test_advance_after_approval_moves_to_next_stage_and_keeps_pending(): void
    {
        $requisitionId = $this->createTestRequisition(
            $this->stageIds["Director's Approval"],
            $this->statusIds['Pending'],
            2
        );

        $requisition = Requisition::findOrFail($requisitionId);

        RequisitionWorkflow::advanceAfterApproval(
            $requisition,
            $this->stageIds["Director's Approval"],
            $this->pipelineId
        );

        $requisition->refresh();

        $this->assertSame($this->statusIds['Pending'], $requisition->status_id);
        $this->assertSame($this->stageIds['Budget Officer'], $requisition->stage_id);
        $this->assertSame(3, $requisition->current_stage_sequence);
    }

    public function test_advance_after_approval_at_final_stage_sets_approved_status(): void
    {
        $requisitionId = $this->createTestRequisition(
            $this->stageIds['Finance Approval'],
            $this->statusIds['Pending'],
            5
        );

        $requisition = Requisition::findOrFail($requisitionId);

        RequisitionWorkflow::advanceAfterApproval(
            $requisition,
            $this->stageIds['Finance Approval'],
            $this->pipelineId
        );

        $requisition->refresh();

        $this->assertSame($this->statusIds['Approved'], $requisition->status_id);
        $this->assertSame($this->stageIds['Finance Approval'], $requisition->stage_id);
        $this->assertSame(5, $requisition->current_stage_sequence);
    }

    public function test_advance_after_approval_does_nothing_when_acting_stage_mismatch(): void
    {
        $requisitionId = $this->createTestRequisition(
            $this->stageIds["Director's Approval"],
            $this->statusIds['Pending'],
            2
        );

        $requisition = Requisition::findOrFail($requisitionId);

        RequisitionWorkflow::advanceAfterApproval(
            $requisition,
            $this->stageIds['Budget Officer'],
            $this->pipelineId
        );

        $requisition->refresh();

        $this->assertSame($this->stageIds["Director's Approval"], $requisition->stage_id);
        $this->assertSame(2, $requisition->current_stage_sequence);
    }

    public function test_apply_rejection_sets_rejected_status_without_changing_stage(): void
    {
        $requisitionId = $this->createTestRequisition(
            $this->stageIds['VP Approval'],
            $this->statusIds['Pending'],
            4
        );

        $requisition = Requisition::findOrFail($requisitionId);

        RequisitionWorkflow::applyRejection($requisition);

        $requisition->refresh();

        $this->assertSame($this->statusIds['Rejected'], $requisition->status_id);
        $this->assertSame($this->stageIds['VP Approval'], $requisition->stage_id);
        $this->assertSame(4, $requisition->current_stage_sequence);
    }

    public function test_apply_cost_center_review_sets_status_without_changing_stage(): void
    {
        $requisitionId = $this->createTestRequisition(
            $this->stageIds['Budget Officer'],
            $this->statusIds['Pending'],
            3
        );

        $requisition = Requisition::findOrFail($requisitionId);

        RequisitionWorkflow::applyCostCenterReview($requisition);

        $requisition->refresh();

        $this->assertSame($this->statusIds['Cost Center Review'], $requisition->status_id);
        $this->assertSame($this->stageIds['Budget Officer'], $requisition->stage_id);
        $this->assertSame(3, $requisition->current_stage_sequence);
    }

    public function test_matching_user_stage_id_when_user_assigned_to_current_stage(): void
    {
        $user = new User(['name' => 'Approver', 'email' => 'approver@ub.edu.bz']);
        $user->id = '11111111-1111-1111-1111-111111111111';
        $user->exists = true;

        UserStage::create([
            'user_id'  => $user->id,
            'stage_id' => $this->stageIds["Director's Approval"],
        ]);
        UserStage::create([
            'user_id'  => $user->id,
            'stage_id' => $this->stageIds['Budget Officer'],
        ]);

        $requisition = new Requisition([
            'stage_id' => $this->stageIds["Director's Approval"],
        ]);

        $this->assertSame(
            $this->stageIds["Director's Approval"],
            RequisitionWorkflow::matchingUserStageId($requisition, $user)
        );
        $this->assertTrue(RequisitionWorkflow::userCanActAtCurrentStage($requisition, $user));
    }

    public function test_matching_user_stage_id_returns_null_when_user_not_assigned_to_current_stage(): void
    {
        $user = new User(['name' => 'Approver', 'email' => 'approver@ub.edu.bz']);
        $user->id = '22222222-2222-2222-2222-222222222222';
        $user->exists = true;

        UserStage::create([
            'user_id'  => $user->id,
            'stage_id' => $this->stageIds['Budget Officer'],
        ]);

        $requisition = new Requisition([
            'stage_id' => $this->stageIds["Director's Approval"],
        ]);

        $this->assertNull(RequisitionWorkflow::matchingUserStageId($requisition, $user));
        $this->assertFalse(RequisitionWorkflow::userCanActAtCurrentStage($requisition, $user));
    }

    public function test_user_with_multiple_stages_can_act_at_each_stage_in_sequence(): void
    {
        $user = new User(['name' => 'Multi Approver', 'email' => 'multi@ub.edu.bz']);
        $user->id = '33333333-3333-3333-3333-333333333333';
        $user->exists = true;

        UserStage::create([
            'user_id'  => $user->id,
            'stage_id' => $this->stageIds["Director's Approval"],
        ]);
        UserStage::create([
            'user_id'  => $user->id,
            'stage_id' => $this->stageIds['Budget Officer'],
        ]);

        $requisitionId = $this->createTestRequisition(
            $this->stageIds["Director's Approval"],
            $this->statusIds['Pending'],
            2
        );

        $requisition = Requisition::findOrFail($requisitionId);

        RequisitionWorkflow::advanceAfterApproval(
            $requisition,
            $this->stageIds["Director's Approval"],
            $this->pipelineId
        );

        $requisition->refresh();

        $this->assertTrue(RequisitionWorkflow::userCanActAtCurrentStage($requisition, $user));
        $this->assertSame($this->stageIds['Budget Officer'], $requisition->stage_id);

        RequisitionWorkflow::advanceAfterApproval(
            $requisition,
            $this->stageIds['Budget Officer'],
            $this->pipelineId
        );

        $requisition->refresh();

        $this->assertFalse(RequisitionWorkflow::userCanActAtCurrentStage($requisition, $user));
        $this->assertSame($this->stageIds['VP Approval'], $requisition->stage_id);
        $this->assertSame($this->statusIds['Pending'], $requisition->status_id);
    }
}
