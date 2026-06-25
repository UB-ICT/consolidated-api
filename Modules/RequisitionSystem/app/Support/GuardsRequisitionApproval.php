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
     * @return array<int, string>
     */
    protected function userStageDecisionStatuses(): array
    {
        return ['approved', 'rejected', 'cost_center_review'];
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

        // 1. Resolve what stage this user matches in user_stages
        $matchingStageId = RequisitionWorkflow::matchingUserStageId($requisition, $user);

        if ($matchingStageId === null) {
            return false;
        }

        // 🔒 2. STRICT ROLE ENFORCEMENT:
        // Even if the user's ID exists in user_stages for Stage 2, 
        // reject them immediately if they do not possess the 'director-dean' role.
        if ($matchingStageId === 2 && !$user->hasAnyRole('director-dean')) {
            return false;
        }

        return true;
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
