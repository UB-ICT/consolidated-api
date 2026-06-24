<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionPurchaseOrder
{
    protected function purchaseOrderEditableStatus(): string
    {
        return 'Approved';
    }

    protected function userIsPurchaseOfficer(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->roles()
            ->where('roles.role_name', 'purchase-officer')
            ->exists();
    }

    protected function canEditPurchaseOrderNumber(Requisition $requisition, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (!$this->userIsPurchaseOfficer($user)) {
            return false;
        }

        return $requisition->status?->name === $this->purchaseOrderEditableStatus();
    }

    protected function assertCanUpdatePurchaseOrderNumber(
        Requisition $requisition,
        ?User $user
    ): void {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        if (!$this->userIsPurchaseOfficer($user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Only purchase officers can update the purchase order number.',
            ], 403));
        }

        $requisition->loadMissing('status');

        if ($requisition->status?->name !== $this->purchaseOrderEditableStatus()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'The purchase order number can only be updated after the requisition is approved.',
            ], 403));
        }
    }
}
