<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Approval;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionApproval
{
    protected function approvableStatuses(): array
    {
        return ['Pending'];
    }

    /**
     * Terminal stage decisions that block further approve/reject/request-review.
     * cost_center_review is intentional pause history — after the cost center
     * resubmits, the same stage assignee must be able to continue.
     *
     * @return array<int, string>
     */
    protected function userStageDecisionStatuses(): array
    {
        return ['approved', 'rejected'];
    }

    protected function findUserStageDecision(Requisition $requisition, User $user): ?Approval
    {
        $actingStageId = RequisitionWorkflow::matchingUserStageId($requisition, $user);

        if (!$actingStageId) {
            return null;
        }

        return Approval::query()
            ->where('requisition_id', $requisition->id)
            ->where('user_id', $user->id)
            ->where('stage_id', $actingStageId)
            ->whereIn('status', $this->userStageDecisionStatuses())
            ->latest('id')
            ->first();
    }

    protected function userCanViewApprovalActions(Requisition $requisition, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (!in_array($requisition->status?->name, $this->approvableStatuses(), true)) {
            return false;
        }

        return RequisitionWorkflow::matchingUserStageId($requisition, $user) !== null;
    }

    protected function canUserApproveRequisition(Requisition $requisition, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (!in_array($requisition->status?->name, $this->approvableStatuses(), true)) {
            return false;
        }

        if (!$this->userCanViewApprovalActions($requisition, $user)) {
            return false;
        }

        return $this->findUserStageDecision($requisition, $user) === null;
    }

    protected function matchingUserStageId(Requisition $requisition, User $user): ?int
    {
        return RequisitionWorkflow::matchingUserStageId($requisition, $user);
    }

    protected function assertUserCanApprove(Requisition $requisition, ?User $user): void
    {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        $requisition->loadMissing('status');

        if (!$this->userCanViewApprovalActions($requisition, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not authorized to act on this requisition at its current stage.',
            ], 403));
        }

        if ($this->findUserStageDecision($requisition, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You have already recorded a decision for this stage.',
            ], 403));
        }

        if (!in_array($requisition->status?->name, $this->approvableStatuses(), true)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'This requisition is not open for approval actions.',
            ], 403));
        }
    }
}
