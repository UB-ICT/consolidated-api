<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

final class RequisitionVisibility
{
    /**
     * Roles that may list every requisition (oversight / payroll tooling).
     *
     * @return list<string>
     */
    public static function viewAllRoles(): array
    {
        return ['super-admin', 'payroll-officer'];
    }

    public static function userCanViewAll(User $user): bool
    {
        return $user->hasAnyRole(self::viewAllRoles());
    }

    public static function userIsPurchaseOfficer(User $user): bool
    {
        return $user->hasAnyRole(['purchase-officer']);
    }

    /**
     * @return Collection<int, int>
     */
    public static function assignedCostCenterIds(User $user): Collection
    {
        if ($user->relationLoaded('costCenters')) {
            return $user->costCenters->pluck('id')->map(fn ($id) => (int) $id);
        }

        return $user->costCenters()
            ->pluck('cost_centers.id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * Restrict a requisition query to records the user is allowed to see:
     * - Pending items currently at a stage assigned to them
     * - Any requisition for their assigned cost center(s)
     * - Approved items when they are a purchase officer
     * - Everything when they have global view access
     */
    public static function constrainQuery(Builder $query, User $user): void
    {
        if (self::userCanViewAll($user)) {
            return;
        }

        $assignedStageIds = RequisitionWorkflow::assignedStageIdsForUser($user);
        $costCenterIds = self::assignedCostCenterIds($user);
        $pendingStatusId = RequisitionWorkflow::pendingStatusId();
        $approvedStatusId = RequisitionWorkflow::approvedStatusId();
        $canViewApproved = self::userIsPurchaseOfficer($user);

        $query->where(function (Builder $visible) use (
            $assignedStageIds,
            $costCenterIds,
            $pendingStatusId,
            $approvedStatusId,
            $canViewApproved
        ) {
            $matched = false;

            if ($assignedStageIds->isNotEmpty() && $pendingStatusId) {
                $matched = true;
                $visible->where(function (Builder $stageQueue) use ($assignedStageIds, $pendingStatusId) {
                    $stageQueue
                        ->where('status_id', $pendingStatusId)
                        ->whereIn('stage_id', $assignedStageIds->all());
                });
            }

            if ($costCenterIds->isNotEmpty()) {
                if ($matched) {
                    $visible->orWhereIn('cost_center_id', $costCenterIds->all());
                } else {
                    $visible->whereIn('cost_center_id', $costCenterIds->all());
                }
                $matched = true;
            }

            if ($canViewApproved && $approvedStatusId) {
                if ($matched) {
                    $visible->orWhere('status_id', $approvedStatusId);
                } else {
                    $visible->where('status_id', $approvedStatusId);
                }
                $matched = true;
            }

            if (!$matched) {
                $visible->whereRaw('1 = 0');
            }
        });
    }

    public static function userCanView(Requisition $requisition, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (self::userCanViewAll($user)) {
            return true;
        }

        $costCenterIds = self::assignedCostCenterIds($user);

        if (
            $requisition->cost_center_id
            && $costCenterIds->contains((int) $requisition->cost_center_id)
        ) {
            return true;
        }

        $requisition->loadMissing('status');

        if (
            self::userIsPurchaseOfficer($user)
            && $requisition->status?->name === 'Approved'
        ) {
            return true;
        }

        if ($requisition->status?->name !== 'Pending') {
            return false;
        }

        return RequisitionWorkflow::userCanActAtCurrentStage($requisition, $user);
    }
}
