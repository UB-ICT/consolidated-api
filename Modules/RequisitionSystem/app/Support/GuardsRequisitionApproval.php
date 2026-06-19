<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionApproval
{
    protected function approvableStatuses(): array
    {
        return ['Pending'];
    }

    protected function canUserApproveRequisition(Requisition $requisition, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (!in_array($requisition->status?->name, $this->approvableStatuses(), true)) {
            return false;
        }

        return RequisitionWorkflow::userCanActAtCurrentStage($requisition, $user);
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

        if (!$this->canUserApproveRequisition($requisition->loadMissing('status'), $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not authorized to act on this requisition at its current stage.',
            ], 403));
        }
    }
}
