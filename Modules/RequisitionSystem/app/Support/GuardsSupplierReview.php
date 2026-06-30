<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;

trait GuardsSupplierReview
{
    protected function supplierReviewerRoles(): array
    {
        return ['director-of-finance', 'super-admin'];
    }

    public function userCanReviewSuppliers(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->roles()
            ->whereIn('roles.role_name', $this->supplierReviewerRoles())
            ->exists();
    }

    protected function assertUserCanReviewSuppliers(?User $user): void
    {
        if (!$this->userCanReviewSuppliers($user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Only the director of finance can approve or reject suppliers.',
            ], 403));
        }
    }
}
