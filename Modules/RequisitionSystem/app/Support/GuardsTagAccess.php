<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Tag;

trait GuardsTagAccess
{
    protected function userAssignedCostCenterIdsForTags(User $user)
    {
        return $user->costCenters()->pluck('cost_centers.id');
    }

    protected function userCanManageTagsForCostCenter(?User $user, int $costCenterId): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->roles()
            ->whereIn('roles.role_name', ['director-of-finance', 'payroll-officer'])
            ->exists()) {
            return true;
        }

        return $this->userAssignedCostCenterIdsForTags($user)->contains($costCenterId);
    }

    protected function assertCanManageTagsForCostCenter(?User $user, int $costCenterId): void
    {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        if (!$this->userCanManageTagsForCostCenter($user, $costCenterId)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You can only manage tags for your assigned cost centers.',
            ], 403));
        }
    }

    protected function assertCanManageTag(?User $user, Tag $tag): void
    {
        $this->assertCanManageTagsForCostCenter($user, (int) $tag->cost_center_id);
    }
}
