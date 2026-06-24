<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionCancellation
{
    protected function nonCancellableStatuses(): array
    {
        return ['Rejected', 'Approved', 'Cancelled'];
    }

    protected function userIsCostCenterOrDirectorDean(User $user): bool
    {
        return $user->roles()
            ->whereIn('roles.role_name', ['requester', 'director-dean'])
            ->exists();
    }

    /**
     * Determine authorization rules for cancelling a requisition sheet.
     * Safely checks runtime role context strings if available.
     */
    protected function userCanCancelRequisition(Requisition $requisition, ?User $user, ?string $currentRole = null): bool
    {
        if (!$user) {
            return false;
        }

        // Safeguard check: If the userHasGlobalRequisitionAccess method exists on the composition class, evaluate it
        if (method_exists($this, 'userHasGlobalRequisitionAccess') && $this->userHasGlobalRequisitionAccess($user, $currentRole)) {
            return false;
        }

        if (in_array($requisition->status?->name, $this->nonCancellableStatuses(), true)) {
            return false;
        }

        if (!$this->userIsCostCenterOrDirectorDean($user)) {
            return false;
        }

        $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

        return $assignedCostCenterIds->contains($requisition->cost_center_id);
    }

    protected function assertUserCanCancel(Requisition $requisition, ?User $user, ?string $currentRole = null): void
    {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        $requisition->loadMissing('status');

        if (!$this->userCanCancelRequisition($requisition, $user, $currentRole)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not authorized to cancel this requisition.',
            ], 403));
        }
    }
}
