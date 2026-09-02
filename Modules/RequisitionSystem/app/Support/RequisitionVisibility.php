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
     * - Purchase officers: pending at purchase stage and approved items
     * - Global viewers: everything
     */
    public static function constrainQuery(Builder $query, User $user): void
    {
        if (self::userCanViewAll($user)) {
            return;
        }

        $assignedStageIds = self::expandedAssignedStageIds($user);
        $costCenterIds = self::assignedCostCenterIds($user);
        $draftStatusId = RequisitionWorkflow::draftStatusId();
        $approvedStatusId = RequisitionWorkflow::approvedStatusId();
        $pendingStatusId = RequisitionWorkflow::pendingStatusId();
        $canViewApproved = self::userIsPurchaseOfficer($user);
        $purchaseStageIds = RequisitionWorkflow::purchaseStageIds();

        $query->where(function (Builder $visible) use (
            $assignedStageIds,
            $costCenterIds,
            $draftStatusId,
            $approvedStatusId,
            $pendingStatusId,
            $canViewApproved,
            $purchaseStageIds
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
                            ->whereRaw(
                                'user_ps.sequence <= COALESCE(requisitions.current_stage_sequence, (
                                    SELECT ps_current.sequence
                                    FROM pipeline_stages ps_current
                                    WHERE ps_current.pipeline_id = requisitions.pipeline_id
                                    AND ps_current.stage_id = requisitions.stage_id
                                    LIMIT 1
                                ))'
                            );
                    });
                });
            }

            if ($costCenterIds->isNotEmpty()) {
                if ($matched) {
                    $visible->orWhere(function (Builder $costCenterScope) use ($costCenterIds) {
                        $costCenterScope
                            ->whereIn('cost_center_id', $costCenterIds->all())
                            ->orWhereIn('reviewing_cost_center_id', $costCenterIds->all());
                    });
                } else {
                    $visible->where(function (Builder $costCenterScope) use ($costCenterIds) {
                        $costCenterScope
                            ->whereIn('cost_center_id', $costCenterIds->all())
                            ->orWhereIn('reviewing_cost_center_id', $costCenterIds->all());
                    });
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

            if (
                $canViewApproved
                && $pendingStatusId
                && $purchaseStageIds !== []
            ) {
                if ($matched) {
                    $visible->orWhere(function (Builder $purchaseQueue) use (
                        $pendingStatusId,
                        $purchaseStageIds
                    ) {
                        $purchaseQueue
                            ->where('status_id', $pendingStatusId)
                            ->whereIn('stage_id', $purchaseStageIds);
                    });
                } else {
                    $visible->where(function (Builder $purchaseQueue) use (
                        $pendingStatusId,
                        $purchaseStageIds
                    ) {
                        $purchaseQueue
                            ->where('status_id', $pendingStatusId)
                            ->whereIn('stage_id', $purchaseStageIds);
                    });
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

        if (
            $requisition->reviewing_cost_center_id
            && $costCenterIds->contains((int) $requisition->reviewing_cost_center_id)
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

        if (
            self::userIsPurchaseOfficer($user)
            && $requisition->status?->name === 'Pending'
            && self::requisitionIsAtPurchaseStage($requisition)
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
        $currentSequence = self::currentStageSequence($requisition, $pipelineId);

        if ($currentSequence === null) {
            return false;
        }

        foreach ($assignedStageIds as $stageId) {
            $stageId = (int) $stageId;

            if (
                RequisitionWorkflow::stagesArePurchaseEquivalent(
                    $stageId,
                    (int) $requisition->stage_id
                )
            ) {
                $maxSequence = RequisitionWorkflow::maxPipelineSequence($pipelineId);

                if ($maxSequence !== null && $currentSequence >= $maxSequence) {
                    return true;
                }
            }

            $userSequence = RequisitionWorkflow::sequenceForStageId($stageId, $pipelineId);

            if ($userSequence !== null && $currentSequence >= $userSequence) {
                return true;
            }
        }

        return false;
    }

    public static function userCanViewOperationalBudget(User $user): bool
    {
        if (self::userCanViewAll($user)) {
            return true;
        }

        if (self::userIsPurchaseOfficer($user)) {
            return true;
        }

        return RequisitionWorkflow::assignedStageIdsForUser($user)->isNotEmpty();
    }

    public static function userCanViewOperationalBudgetForCostCenter(
        User $user,
        int $costCenterId
    ): bool {
        if (self::userCanViewOperationalBudget($user)) {
            return true;
        }

        if (self::assignedCostCenterIds($user)->contains($costCenterId)) {
            return true;
        }

        $reviewStatusId = RequisitionWorkflow::costCenterReviewStatusId();

        if (!$reviewStatusId) {
            return false;
        }

        $reviewerCostCenterIds = self::assignedCostCenterIds($user);

        if ($reviewerCostCenterIds->isEmpty()) {
            return false;
        }

        return Requisition::query()
            ->where('cost_center_id', $costCenterId)
            ->where('status_id', $reviewStatusId)
            ->whereIn('reviewing_cost_center_id', $reviewerCostCenterIds->all())
            ->exists();
    }

    /**
     * @return Collection<int, int>
     */
    private static function expandedAssignedStageIds(User $user): Collection
    {
        $assignedStageIds = RequisitionWorkflow::assignedStageIdsForUser($user);
        $expanded = collect();

        foreach ($assignedStageIds as $stageId) {
            $stageId = (int) $stageId;
            $expanded->push($stageId);

            foreach (RequisitionWorkflow::purchaseStageIds() as $purchaseStageId) {
                if (RequisitionWorkflow::stagesArePurchaseEquivalent($stageId, $purchaseStageId)) {
                    $expanded->push($purchaseStageId);
                }
            }
        }

        return $expanded->unique()->values();
    }

    private static function requisitionIsAtPurchaseStage(Requisition $requisition): bool
    {
        if (!$requisition->stage_id) {
            return false;
        }

        return in_array(
            (int) $requisition->stage_id,
            RequisitionWorkflow::purchaseStageIds(
                RequisitionWorkflow::pipelineIdFor($requisition)
            ),
            true
        );
    }

    private static function currentStageSequence(
        Requisition $requisition,
        int $pipelineId
    ): ?int {
        if ($requisition->current_stage_sequence !== null) {
            return (int) $requisition->current_stage_sequence;
        }

        return RequisitionWorkflow::sequenceForStageId(
            (int) $requisition->stage_id,
            $pipelineId
        );
    }
}
