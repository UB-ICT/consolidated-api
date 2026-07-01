<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionEditing
{
    protected function costCenterEditableStatuses(): array
    {
        return ['Draft', 'Cost Center Review', 'Rejected'];
    }

    /**
     * Roles that review requisitions system-wide rather than within a single
     * cost center (e.g. director-dean is scoped to their own cost center).
     */
    protected function globalRequisitionRoles(): array
    {
        return [
            'budget-officer',
            'vice-president',
            'director-of-finance',
            'purchase-officer',
            'payroll-officer',
            'super-admin'
        ];
    }

    /**
     * Check if a user/role combination bypasses Cost Center isolation sandboxing.
     * Centralized to accept both single argument calls and dynamic role overrides.
     */
    protected function userHasGlobalRequisitionAccess(User $user, ?string $currentRole = null): bool
    {
        $globalRoles = $this->globalRequisitionRoles();

        // 1. If an explicit runtime role override was requested (like via dashboard or query string), validate it
        if ($currentRole && in_array($currentRole, $globalRoles, true)) {
            return true;
        }

        // 2. Otherwise, fallback to checking if the user holds any of these administrative profiles in the database
        return $user->roles()
            ->whereIn('roles.role_name', $globalRoles)
            ->exists();
    }

    protected function assertCostCenterCanEdit(Requisition $requisition, ?User $user): void
    {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        if ($this->userHasGlobalRequisitionAccess($user)) {
            return;
        }

        $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

        if (!$assignedCostCenterIds->contains($requisition->cost_center_id)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not authorized to edit this requisition.',
            ], 403));
        }

        $statusName = $requisition->status?->name;

        if (!in_array($statusName, $this->costCenterEditableStatuses(), true)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'This requisition can only be edited while it is in Draft or Cost Center Review status.',
            ], 403));
        }
    }

    protected function isEditableByCostCenter(Requisition $requisition, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->userHasGlobalRequisitionAccess($user)) {
            return true;
        }

        $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

        if (!$assignedCostCenterIds->contains($requisition->cost_center_id)) {
            return false;
        }

        return in_array(
            $requisition->status?->name,
            $this->costCenterEditableStatuses(),
            true
        );
    }

    protected function assertLineItemsNotAdded(
        Requisition $requisition,
        array $newItems,
        ?User $user
    ): void {
        if (!$user || $this->userHasGlobalRequisitionAccess($user)) {
            return;
        }

        if ($requisition->status?->name === 'Draft') {
            return;
        }

        $previousCount = $requisition->items()->count();
        $newCount = count($newItems);

        if ($newCount > $previousCount) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'New line items cannot be added after this requisition has been submitted.',
            ], 422));
        }
    }
}
