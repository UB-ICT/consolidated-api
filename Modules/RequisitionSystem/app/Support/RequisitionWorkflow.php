<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Models\UserStage;

final class RequisitionWorkflow
{
    public const PIPELINE_NAME = 'operations';

    public const DEFAULT_PIPELINE_ID = 1;

    public const DRAFT_STAGE_SEQUENCE = 1;

    public const SUBMITTED_STAGE_SEQUENCE = 2;

    public const BUDGET_OFFICER_STAGE_SEQUENCE = 3;

    /** @var list<string> */
    private const PURCHASE_STAGE_NAMES = [
        'Purchase Approval',
        'Purchase Officer Approval',
        'Purchase Officer',
    ];

    public static function defaultPipelineId(): int
    {
        return (int) (Pipeline::query()
            ->where('name', self::PIPELINE_NAME)
            ->value('id')
            ?? Pipeline::query()->value('id')
            ?? self::DEFAULT_PIPELINE_ID);
    }

    public static function pipelineIdFor(Requisition $requisition): int
    {
        if ($requisition->pipeline_id) {
            return (int) $requisition->pipeline_id;
        }

        return self::defaultPipelineId();
    }

    public static function stageIdForSequence(int $sequence, ?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::defaultPipelineId();

        $stageId = DB::connection('porsql')
            ->table('pipeline_stages')
            ->where('pipeline_id', $pipelineId)
            ->where('sequence', $sequence)
            ->value('stage_id');

        return $stageId !== null ? (int) $stageId : null;
    }

    public static function sequenceForStageId(int $stageId, ?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::defaultPipelineId();

        $sequence = DB::connection('porsql')
            ->table('pipeline_stages')
            ->where('pipeline_id', $pipelineId)
            ->where('stage_id', $stageId)
            ->value('sequence');

        return $sequence !== null ? (int) $sequence : null;
    }

    public static function maxPipelineSequence(?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::defaultPipelineId();

        $max = DB::connection('porsql')
            ->table('pipeline_stages')
            ->where('pipeline_id', $pipelineId)
            ->max('sequence');

        return $max !== null ? (int) $max : null;
    }

    public static function nextStageIdForCurrentStage(int $currentStageId, ?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::defaultPipelineId();
        $currentSequence = self::sequenceForStageId($currentStageId, $pipelineId);

        if ($currentSequence === null) {
            return null;
        }

        return self::stageIdForSequence($currentSequence + 1, $pipelineId);
    }

    public static function previousStageIdForStage(int $stageId, ?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::defaultPipelineId();
        $currentSequence = self::sequenceForStageId($stageId, $pipelineId);

        if ($currentSequence === null || $currentSequence <= self::SUBMITTED_STAGE_SEQUENCE) {
            return null;
        }

        return self::stageIdForSequence($currentSequence - 1, $pipelineId);
    }

    /**
     * Maps workflow role names to the POR pipeline stage they act on.
     *
     * @return array<string, int>
     */
    public static function roleStageMapping(?int $pipelineId = null): array
    {
        $pipelineId ??= self::defaultPipelineId();

        $mapping = [];

        foreach ([
            'director-dean'       => 2,
            'budget-officer'      => 3,
            'vice-president'      => 4,
            'director-of-finance' => 5,
            'purchase-officer'    => 6,
        ] as $role => $sequence) {
            $stageId = self::stageIdForSequence($sequence, $pipelineId);

            if ($stageId !== null) {
                $mapping[$role] = $stageId;
            }
        }

        return $mapping;
    }

    public static function isLastPipelineStage(int $stageId, ?int $pipelineId = null): bool
    {
        $pipelineId ??= self::defaultPipelineId();
        $sequence = self::sequenceForStageId($stageId, $pipelineId);
        $maxSequence = self::maxPipelineSequence($pipelineId);

        if ($sequence === null || $maxSequence === null) {
            return false;
        }

        return $sequence >= $maxSequence;
    }

    /**
     * @return Collection<int, int>
     */
    public static function assignedStageIdsForUser(User $user): Collection
    {
        return UserStage::query()
            ->where('user_id', $user->id)
            ->pluck('stage_id')
            ->map(fn ($stageId) => (int) $stageId);
    }

    /**
     * @return list<int>
     */
    public static function purchaseStageIds(?int $pipelineId = null): array
    {
        $pipelineId ??= self::defaultPipelineId();
        $fromNames = Stage::query()
            ->whereIn('name', self::PURCHASE_STAGE_NAMES)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $mappedId = self::roleStageMapping($pipelineId)['purchase-officer'] ?? null;

        if ($mappedId !== null) {
            $fromNames[] = (int) $mappedId;
        }

        return array_values(array_unique($fromNames));
    }

    public static function stagesArePurchaseEquivalent(int $firstStageId, int $secondStageId): bool
    {
        if ($firstStageId === $secondStageId) {
            return true;
        }

        $purchaseStageIds = self::purchaseStageIds();

        return in_array($firstStageId, $purchaseStageIds, true)
            && in_array($secondStageId, $purchaseStageIds, true);
    }

    public static function matchingUserStageId(Requisition $requisition, User $user): ?int
    {
        if (!$requisition->stage_id) {
            return null;
        }

        $currentStageId = (int) $requisition->stage_id;
        $assignedStageIds = self::assignedStageIdsForUser($user);

        if ($assignedStageIds->contains($currentStageId)) {
            return $currentStageId;
        }

        foreach ($assignedStageIds as $assignedStageId) {
            if (self::stagesArePurchaseEquivalent((int) $assignedStageId, $currentStageId)) {
                return $currentStageId;
            }
        }

        $pipelineId = self::pipelineIdFor($requisition);
        $purchaseStageId = self::roleStageMapping($pipelineId)['purchase-officer'] ?? null;

        if (
            $purchaseStageId !== null
            && (int) $purchaseStageId === $currentStageId
            && $user->hasAnyRole(['purchase-officer'])
        ) {
            return $currentStageId;
        }

        return null;
    }

    public static function userCanActAtCurrentStage(Requisition $requisition, User $user): bool
    {
        return self::matchingUserStageId($requisition, $user) !== null;
    }

    public static function draftStatusId(): ?int
    {
        return Status::where('name', 'Draft')->value('id');
    }

    public static function pendingStatusId(): ?int
    {
        return Status::where('name', 'Pending')->value('id');
    }

    public static function approvedStatusId(): ?int
    {
        return Status::where('name', 'Approved')->value('id');
    }

    public static function rejectedStatusId(): ?int
    {
        return Status::where('name', 'Rejected')->value('id');
    }

    public static function costCenterReviewStatusId(): ?int
    {
        return Status::where('name', 'Cost Center Review')->value('id');
    }

    public static function cancelledStatusId(): ?int
    {
        return Status::where('name', 'Cancelled')->value('id');
    }

    public static function closedStatusId(): ?int
    {
        return Status::where('name', 'Closed')->value('id');
    }

    public static function applyDraftState(array &$data, ?int $pipelineId = null): void
    {
        $pipelineId ??= self::defaultPipelineId();
        $draftStageId = self::stageIdForSequence(self::DRAFT_STAGE_SEQUENCE, $pipelineId)
            ?? Stage::query()->value('id');

        $data['pipeline_id'] = $pipelineId;
        $data['status_id'] = self::draftStatusId() ?? $data['status_id'] ?? Status::query()->value('id');
        $data['stage_id'] = $draftStageId;
        $data['current_stage_sequence'] = self::DRAFT_STAGE_SEQUENCE;
    }

    /**
     * Landing sequence after submit. Director-deans skip their own approval
     * stage and go straight to budget-officer review.
     */
    public static function submitSequenceForUser(?User $submitter = null): int
    {
        if ($submitter?->hasAnyRole(['director-dean'])) {
            return self::BUDGET_OFFICER_STAGE_SEQUENCE;
        }

        return self::SUBMITTED_STAGE_SEQUENCE;
    }

    public static function skipsDirectorApprovalOnSubmit(?User $submitter = null): bool
    {
        return self::submitSequenceForUser($submitter) === self::BUDGET_OFFICER_STAGE_SEQUENCE;
    }

    public static function applySubmitState(
        array &$data,
        ?int $pipelineId = null,
        ?User $submitter = null
    ): void {
        $pipelineId ??= self::defaultPipelineId();
        $sequence = self::submitSequenceForUser($submitter);
        $submittedStageId = self::stageIdForSequence($sequence, $pipelineId)
            ?? Stage::query()->value('id');

        $data['pipeline_id'] = $pipelineId;
        $data['status_id'] = self::pendingStatusId() ?? $data['status_id'] ?? Status::query()->value('id');
        $data['stage_id'] = $submittedStageId;
        $data['current_stage_sequence'] = $sequence;
    }

    public static function applyResubmitFromCostCenterReview(array &$data, Requisition $requisition): void
    {
        $data['pipeline_id'] = self::pipelineIdFor($requisition);
        $data['status_id'] = self::pendingStatusId() ?? $requisition->status_id;
        $data['stage_id'] = $requisition->stage_id;
        $data['current_stage_sequence'] = $requisition->current_stage_sequence;
    }

    /**
     * Advance the requisition to the next pipeline stage after approval.
     * Status remains Pending until the final stage is approved, then becomes Approved.
     */
    public static function advanceAfterApproval(
        Requisition $requisition,
        int $actingStageId,
        ?int $pipelineId = null
    ): void {
        $pipelineId ??= self::pipelineIdFor($requisition);

        if ((int) $requisition->stage_id !== $actingStageId) {
            return;
        }

        $nextStageId = self::nextStageIdForCurrentStage($actingStageId, $pipelineId);

        if ($nextStageId !== null) {
            $nextSequence = self::sequenceForStageId($nextStageId, $pipelineId);

            $requisition->update([
                'status_id'              => self::pendingStatusId() ?? $requisition->status_id,
                'stage_id'               => $nextStageId,
                'current_stage_sequence' => $nextSequence,
            ]);

            return;
        }

        $requisition->update([
            'status_id' => self::approvedStatusId() ?? $requisition->status_id,
        ]);
    }

    public static function applyRejection(Requisition $requisition): void
    {
        $requisition->update([
            'status_id' => self::rejectedStatusId() ?? $requisition->status_id,
        ]);
    }

    public static function applyCostCenterReview(
        Requisition $requisition,
        bool $clearDelegatedReview = true
    ): void {
        $updates = [
            'status_id' => self::costCenterReviewStatusId() ?? $requisition->status_id,
        ];

        if ($clearDelegatedReview) {
            $updates['reviewing_cost_center_id'] = null;
        }

        $requisition->update($updates);
    }

    public static function applyDelegatedCostCenterReview(
        Requisition $requisition,
        int $reviewingCostCenterId
    ): void {
        $requisition->update([
            'status_id'               => self::costCenterReviewStatusId() ?? $requisition->status_id,
            'reviewing_cost_center_id' => $reviewingCostCenterId,
        ]);
    }

    public static function clearDelegatedCostCenterReview(Requisition $requisition): void
    {
        if ($requisition->reviewing_cost_center_id === null) {
            return;
        }

        $requisition->update([
            'reviewing_cost_center_id' => null,
        ]);
    }

    public static function applyCancellation(Requisition $requisition): void
    {
        $requisition->update([
            'status_id' => self::cancelledStatusId() ?? $requisition->status_id,
        ]);
    }

    public static function applyClosure(Requisition $requisition): void
    {
        $requisition->update([
            'status_id' => self::closedStatusId() ?? $requisition->status_id,
        ]);
    }
}
