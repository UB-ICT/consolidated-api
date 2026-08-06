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
     * - Cost-center members: their cost center's requisitions at any status
     * - Stage assignees: non-draft items at or past their assigned pipeline stage
     * - Purchase officers: approved items
     * - Global viewers: everything
     */
    public static function constrainQuery(Builder $query, User $user): void
    {
        if (self::userCanViewAll($user)) {
            return;
        }

        $assignedStageIds = RequisitionWorkflow::assignedStageIdsForUser($user);
        $costCenterIds = self::assignedCostCenterIds($user);
        $draftStatusId = RequisitionWorkflow::draftStatusId();
        $approvedStatusId = RequisitionWorkflow::approvedStatusId();
        $canViewApproved = self::userIsPurchaseOfficer($user);

        $query->where(function (Builder $visible) use (
            $assignedStageIds,
            $costCenterIds,
            $draftStatusId,
            $approvedStatusId,
            $canViewApproved
        ) {
            $matched = false;

            if ($assignedStageIds->isNotEmpty()) {
                $matched = true;
                $visible->where(function (Builder $stageQueue) use ($assignedStageIds, $draftStatusId) {
                    if ($draftStatusId) {
                        $stageQueue->where('status_id', '!=', $draftStatusId);
                    }

                    // At or past any assigned stage (sequence), excluding drafts.
                    $stageQueue->whereExists(function ($sub) use ($assignedStageIds) {
                        $sub->selectRaw('1')
                            ->from('pipeline_stages as user_ps')
                            ->whereColumn('user_ps.pipeline_id', 'requisitions.pipeline_id')
                            ->whereIn('user_ps.stage_id', $assignedStageIds->all())
                            ->whereColumn(
                                'user_ps.sequence',
                                '<=',
                                'requisitions.current_stage_sequence'
                            );
                    });
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

        if ($requisition->status?->name === 'Draft') {
            return false;
        }

        return self::userHasReachedStage($requisition, $user);
    }

    /**
     * True when the requisition is at or past any pipeline stage assigned to the user.
     */
    public static function userHasReachedStage(Requisition $requisition, User $user): bool
    {
        $assignedStageIds = RequisitionWorkflow::assignedStageIdsForUser($user);

        if ($assignedStageIds->isEmpty() || !$requisition->stage_id) {
            return false;
        }

        $pipelineId = RequisitionWorkflow::pipelineIdFor($requisition);
        $currentSequence = $requisition->current_stage_sequence !== null
            ? (int) $requisition->current_stage_sequence
            : RequisitionWorkflow::sequenceForStageId((int) $requisition->stage_id, $pipelineId);

        if ($currentSequence === null) {
            return false;
        }

        foreach ($assignedStageIds as $stageId) {
            $userSequence = RequisitionWorkflow::sequenceForStageId((int) $stageId, $pipelineId);

            if ($userSequence !== null && $currentSequence >= $userSequence) {
                return true;
            }
        }

        return false;
    }
}
