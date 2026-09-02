<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionCostCenterReview
{
    protected function forwardableReviewStatuses(): array
    {
        return ['Pending', 'Cost Center Review'];
    }

    protected function assertUserCanForwardReview(Requisition $requisition, ?User $user): void
    {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        $requisition->loadMissing('status');

        if (!$user->hasAnyRole(['budget-officer'])) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Only a budget officer can forward a requisition for review by another cost center.',
            ], 403));
        }

        if (!in_array($requisition->status?->name, $this->forwardableReviewStatuses(), true)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'This requisition cannot be forwarded for review in its current status.',
            ], 403));
        }

        $pipelineId = RequisitionWorkflow::pipelineIdFor($requisition);
        $budgetStageId = RequisitionWorkflow::roleStageMapping($pipelineId)['budget-officer'] ?? null;

        if (!$budgetStageId || (int) $requisition->stage_id !== (int) $budgetStageId) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Review can only be forwarded while the requisition is at the budget officer stage.',
            ], 403));
        }

        if (RequisitionWorkflow::matchingUserStageId($requisition, $user) === null) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not assigned to act at the budget officer stage for this requisition.',
            ], 403));
        }
    }

    protected function userIsAssignedToCostCenter(User $user, int $costCenterId): bool
    {
        return $user->costCenters()
            ->where('cost_centers.id', $costCenterId)
            ->exists();
    }

    protected function requisitionHasDelegatedReview(Requisition $requisition): bool
    {
        return $requisition->reviewing_cost_center_id !== null;
    }
}
