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
        if (!$user || !$this->userIsPurchaseOfficer($user)) {
            return false;
        }

        $requisition->loadMissing('status');

        return $requisition->status?->name === $this->purchaseOrderEditableStatus();
    }

    protected function canManagePurchaseOrderDocument(Requisition $requisition, ?User $user): bool
    {
        return $this->canEditPurchaseOrderNumber($requisition, $user);
    }

    protected function canEmailPurchaseOrder(Requisition $requisition, ?User $user): bool
    {
        if (!$this->canManagePurchaseOrderDocument($requisition, $user)) {
            return false;
        }

        if (!$requisition->purchase_order_file_path) {
            return false;
        }

        $supplier = $requisition->preferredSupplier();

        return filled($supplier?->email);
    }

    protected function assertCanUpdatePurchaseOrderNumber(
        Requisition $requisition,
        ?User $user
    ): void {
        $this->assertCanManagePurchaseOrder($requisition, $user, 'update the purchase order number');
    }

    protected function assertCanManagePurchaseOrderDocument(
        Requisition $requisition,
        ?User $user
    ): void {
        $this->assertCanManagePurchaseOrder($requisition, $user, 'manage the purchase order document');
    }

    protected function assertCanEmailPurchaseOrder(
        Requisition $requisition,
        ?User $user
    ): void {
        $this->assertCanManagePurchaseOrder($requisition, $user, 'email the purchase order');

        if (!$requisition->purchase_order_file_path) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Upload a purchase order PDF before emailing the supplier.',
            ], 422));
        }

        $supplier = $requisition->preferredSupplier();

        if (!$supplier) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Select a preferred supplier before emailing the purchase order.',
            ], 422));
        }

        if (!filled($supplier->email)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'The preferred supplier does not have an email address.',
            ], 422));
        }
    }

    private function assertCanManagePurchaseOrder(
        Requisition $requisition,
        ?User $user,
        string $action
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
                'message' => "Only purchase officers can {$action}.",
            ], 403));
        }

        if ($this->canEditPurchaseOrderNumber($requisition, $user)) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The purchase order can only be managed after the requisition is approved.',
        ], 403));
    }
}
