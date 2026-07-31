<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionClosure
{
    protected function nonClosableStatuses(): array
    {
        return ['Rejected', 'Cancelled', 'Closed'];
    }

    protected function userCanCloseRequisition(Requisition $requisition, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (in_array($requisition->status?->name, $this->nonClosableStatuses(), true)) {
            return false;
        }

        $isPurchaseOfficer = $user->roles()
            ->where('roles.role_name', 'purchase-officer')
            ->exists();

        if ($isPurchaseOfficer || $this->userHasGlobalRequisitionAccess($user)) {
            return true;
        }

        if (!$this->userIsCostCenterOrDirectorDean($user)) {
            return false;
        }

        $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

        return $assignedCostCenterIds->contains($requisition->cost_center_id);
    }

    protected function assertUserCanClose(Requisition $requisition, ?User $user): void
    {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        $requisition->loadMissing('status');

        if (!$this->userCanCloseRequisition($requisition, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not authorized to close this requisition.',
            ], 403));
        }
    }
}
