<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Models\UserStage;

final class BudgetWorkflow
{
    public const PIPELINE_NAME = 'budget';

    public const DRAFT_STAGE_SEQUENCE = 1;

    public const BUDGET_OFFICER_SEQUENCE = 2;

    public static function pipelineId(): int
    {
        return (int) (Pipeline::query()
            ->where('name', self::PIPELINE_NAME)
            ->value('id') ?? 0);
    }

    public static function pipelineIdFor(Budget $budget): int
    {
        if ($budget->pipeline_id) {
            return (int) $budget->pipeline_id;
        }

        return self::pipelineId();
    }

    public static function stageIdForSequence(int $sequence, ?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::pipelineId();

        if (!$pipelineId) {
            return null;
        }

        $stageId = DB::connection('porsql')
            ->table('pipeline_stages')
            ->where('pipeline_id', $pipelineId)
            ->where('sequence', $sequence)
            ->value('stage_id');

        return $stageId !== null ? (int) $stageId : null;
    }

    public static function sequenceForStageId(int $stageId, ?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::pipelineId();

        if (!$pipelineId) {
            return null;
        }

        $sequence = DB::connection('porsql')
            ->table('pipeline_stages')
            ->where('pipeline_id', $pipelineId)
            ->where('stage_id', $stageId)
            ->value('sequence');

        return $sequence !== null ? (int) $sequence : null;
    }

    public static function nextStageIdForCurrentStage(int $currentStageId, ?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::pipelineId();
        $currentSequence = self::sequenceForStageId($currentStageId, $pipelineId);

        if ($currentSequence === null) {
            return null;
        }

        return self::stageIdForSequence($currentSequence + 1, $pipelineId);
    }

    public static function maxPipelineSequence(?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::pipelineId();

        if (!$pipelineId) {
            return null;
        }

        $max = DB::connection('porsql')
            ->table('pipeline_stages')
            ->where('pipeline_id', $pipelineId)
            ->max('sequence');

        return $max !== null ? (int) $max : null;
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

    public static function matchingUserStageId(Budget $budget, User $user): ?int
    {
        if (!$budget->stage_id) {
            return null;
        }

        $assignedStageIds = self::assignedStageIdsForUser($user);

        if (!$assignedStageIds->contains((int) $budget->stage_id)) {
            return null;
        }

        return (int) $budget->stage_id;
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

    public static function activeStatusId(): ?int
    {
        return Status::where('name', 'Active')->value('id');
    }

    public static function inactiveStatusId(): ?int
    {
        return Status::where('name', 'Inactive')->value('id');
    }

    /**
     * Budget lifecycle statuses (shared statuses table also holds POR-only names).
     *
     * @return array<int, string>
     */
    public static function budgetStatuses(): array
    {
        return ['Draft', 'Pending', 'Approved', 'Active', 'Inactive'];
    }

    /**
     * In-flight budgets — only one of these may exist per cost center.
     *
     * @return array<int, string>
     */
    public static function inFlightStatuses(): array
    {
        return ['Draft', 'Pending'];
    }

    /**
     * @deprecated Use inFlightStatuses()
     *
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return self::inFlightStatuses();
    }

    /**
     * Terminal statuses that lock line-item editing.
     *
     * @return array<int, string>
     */
    public static function lockedStatuses(): array
    {
        return ['Approved', 'Active', 'Inactive'];
    }

    public static function applyDraftState(array &$data): void
    {
        $pipelineId = self::pipelineId();
        $draftStageId = self::stageIdForSequence(self::DRAFT_STAGE_SEQUENCE, $pipelineId)
            ?? Stage::query()->value('id');

        $data['pipeline_id'] = $pipelineId ?: null;
        $data['status_id'] = self::draftStatusId() ?? $data['status_id'] ?? null;
        $data['stage_id'] = $draftStageId;
        $data['current_stage_sequence'] = self::DRAFT_STAGE_SEQUENCE;
        $data['submitted_at'] = null;
    }

    public static function applySubmitState(Budget $budget): void
    {
        $pipelineId = self::pipelineIdFor($budget);
        $submittedStageId = self::stageIdForSequence(self::BUDGET_OFFICER_SEQUENCE, $pipelineId)
            ?? $budget->stage_id;

        $budget->update([
            'pipeline_id'            => $pipelineId ?: $budget->pipeline_id,
            'status_id'              => self::pendingStatusId() ?? $budget->status_id,
            'stage_id'               => $submittedStageId,
            'current_stage_sequence' => self::BUDGET_OFFICER_SEQUENCE,
            'submitted_at'           => now(),
        ]);
    }

    public static function advanceAfterApproval(Budget $budget, int $actingStageId): void
    {
        if ((int) $budget->stage_id !== $actingStageId) {
            return;
        }

        $pipelineId = self::pipelineIdFor($budget);
        $nextStageId = self::nextStageIdForCurrentStage($actingStageId, $pipelineId);

        if ($nextStageId !== null) {
            $nextSequence = self::sequenceForStageId($nextStageId, $pipelineId);

            $budget->update([
                'status_id'              => self::pendingStatusId() ?? $budget->status_id,
                'stage_id'               => $nextStageId,
                'current_stage_sequence' => $nextSequence,
            ]);

            return;
        }

        $budget->update([
            'status_id' => self::approvedStatusId() ?? $budget->status_id,
        ]);
    }

    public static function applyRejection(Budget $budget): void
    {
        $budget->update([
            'status_id' => self::inactiveStatusId() ?? $budget->status_id,
        ]);
    }

    /**
     * Send the budget back to the cost center as Draft for revisions.
     */
    public static function applyCostCenterReview(Budget $budget): void
    {
        $pipelineId = self::pipelineIdFor($budget);
        $draftStageId = self::stageIdForSequence(self::DRAFT_STAGE_SEQUENCE, $pipelineId)
            ?? $budget->stage_id;

        $budget->update([
            'status_id'              => self::draftStatusId() ?? $budget->status_id,
            'stage_id'               => $draftStageId,
            'current_stage_sequence' => self::DRAFT_STAGE_SEQUENCE,
        ]);
    }

    /**
     * Promote an Approved budget to Active and deactivate any prior Active budget
     * for the same cost center.
     */
    public static function applyActivation(Budget $budget): void
    {
        $activeStatusId = self::activeStatusId();
        $inactiveStatusId = self::inactiveStatusId();

        if ($activeStatusId === null) {
            return;
        }

        if ($inactiveStatusId !== null) {
            Budget::query()
                ->where('cost_center_id', $budget->cost_center_id)
                ->where('id', '!=', $budget->id)
                ->where('status_id', $activeStatusId)
                ->update(['status_id' => $inactiveStatusId]);
        }

        $budget->update([
            'status_id' => $activeStatusId,
        ]);
    }

    public static function applyDeactivation(Budget $budget): void
    {
        $budget->update([
            'status_id' => self::inactiveStatusId() ?? $budget->status_id,
        ]);
    }
}
