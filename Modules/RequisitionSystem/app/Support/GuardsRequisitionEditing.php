<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionEditing
{
    protected function costCenterEditableStatuses(): array
    {
        return ['Draft', 'Cost Center Review'];
    }

    protected function userHasGlobalRequisitionAccess(User $user): bool
    {
        return $user->roles()
            ->whereIn('roles.role_name', ['director-of-finance', 'payroll-officer'])
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

        if (in_array($requisition->status?->name, $this->costCenterEditableStatuses(), true)) {
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

    protected function countCompleteLineItems(Requisition $requisition): int
    {
        return $requisition->items()
            ->whereNotNull('chart_of_account_id')
            ->where(function ($query) {
                $query->where('total', '>', 0)
                    ->orWhere('unit_cost', '>', 0);
            })
            ->count();
    }

    /**
     * @param  list<array<string, mixed>>  $pricedItems
     */
    protected function countCompletePricedItems(array $pricedItems): int
    {
        return collect($pricedItems)
            ->filter(function (array $item) {
                if (empty($item['chart_of_account_id'])) {
                    return false;
                }

                return (float) ($item['total'] ?? 0) > 0
                    || (float) ($item['unit_cost'] ?? 0) > 0;
            })
            ->count();
    }

    /**
     * @param  list<array<string, mixed>>  $pricedItems
     */
    protected function wouldWipeExistingLineItems(
        Requisition $requisition,
        array $pricedItems
    ): bool {
        $existingComplete = $this->countCompleteLineItems($requisition);

        if ($existingComplete === 0) {
            return false;
        }

        return $this->countCompletePricedItems($pricedItems) < $existingComplete;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function mapPersistedItemsForPricing(Requisition $requisition): array
    {
        return $requisition->items()
            ->get()
            ->map(fn ($item) => [
                'chart_of_account_id' => $item->chart_of_account_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'gst_applicable' => (bool) $item->gst_applicable,
                'comments' => $item->comments,
            ])
            ->values()
            ->all();
    }
}
