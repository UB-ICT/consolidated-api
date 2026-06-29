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
use Illuminate\Support\Facades\Auth;

final class RequisitionWorkflow
{
    public const DEFAULT_PIPELINE_ID = 1;

    public const DRAFT_STAGE_SEQUENCE = 1;

    public const SUBMITTED_STAGE_SEQUENCE = 2;

    private const STAGE_ROLE_MAP = [
        2 => 'director-dean',
        3 => 'budget-officer',
        4 => 'vice-president',
        5 => 'director-of-finance',
        6 => 'purchase-officer',
    ];

    public static function requiredRoleForStageId(int $stageId): ?string
    {
        return self::STAGE_ROLE_MAP[$stageId] ?? null;
    }

    public static function defaultPipelineId(): int
    {
        return Pipeline::query()->value('id') ?? self::DEFAULT_PIPELINE_ID;
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
            ->map(fn($stageId) => (int) $stageId);
    }

    public static function matchingUserStageId(Requisition $requisition, User $user): ?int
    {
        if (!$requisition->stage_id) {
            return null;
        }

        $stageId = (int) $requisition->stage_id;
        $assignedStageIds = self::assignedStageIdsForUser($user);

        if ($assignedStageIds->contains($stageId)) {
            return $stageId;
        }

        // The user_stages snapshot for this stage is only populated once, when the
        // previous stage was approved, for whoever held the required role at that
        // moment. A user who acquires the role afterward (e.g. a role change) would
        // otherwise be permanently locked out even though they're now qualified.
        $requiredRole = self::requiredRoleForStageId($stageId);

        if ($requiredRole !== null && $user->hasAnyRole($requiredRole)) {
            UserStage::query()->updateOrInsert(
                ['user_id' => $user->id, 'stage_id' => $stageId],
                ['created_at' => now(), 'updated_at' => now()]
            );

            return $stageId;
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

    public static function applyDraftState(array &$data, ?int $pipelineId = null): void
    {
        $pipelineId ??= self::defaultPipelineId();
        $draftStageId = self::stageIdForSequence(self::DRAFT_STAGE_SEQUENCE, $pipelineId)
            ?? Stage::query()->value('id');

        $data['status_id'] = self::draftStatusId() ?? $data['status_id'] ?? Status::query()->value('id');
        $data['stage_id'] = $draftStageId;
        $data['current_stage_sequence'] = self::DRAFT_STAGE_SEQUENCE;
    }

    public static function applySubmitState(array &$data, ?int $pipelineId = null): void
    {
        $pipelineId ??= self::defaultPipelineId();
        $submittedStageId = self::stageIdForSequence(self::SUBMITTED_STAGE_SEQUENCE, $pipelineId)
            ?? Stage::query()->value('id');

        $data['status_id'] = self::pendingStatusId() ?? $data['status_id'] ?? Status::query()->value('id');
        $data['stage_id'] = $submittedStageId;
        $data['current_stage_sequence'] = self::SUBMITTED_STAGE_SEQUENCE;
    }

    public static function applyResubmitFromCostCenterReview(array &$data, Requisition $requisition): void
    {
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
        $pipelineId ??= self::defaultPipelineId();

        if ((int) $requisition->stage_id !== $actingStageId) {
            return;
        }

        $nextStageId = self::nextStageIdForCurrentStage($actingStageId, $pipelineId);

        if ($nextStageId !== null) {
            $nextSequence = self::sequenceForStageId($nextStageId, $pipelineId);

            // 1. Update the requisition record itself
            $requisition->update([
                'status_id'              => self::pendingStatusId() ?? $requisition->status_id,
                'stage_id'               => $nextStageId,
                'current_stage_sequence' => $nextSequence,
            ]);

            // 2. 🔄 Sync the database table for the next group of approvers
            self::syncUserStagesForNextStep($requisition, $nextStageId);

            return;
        }

        // 3. Final Step Approved: Clear out any remaining active permissions for this form
        DB::connection('porsql')->table('user_stages')
            ->where('stage_id', $actingStageId)
            ->delete();

        $requisition->update([
            'status_id' => self::approvedStatusId() ?? $requisition->status_id,
        ]);
    }

    private static function syncUserStagesForNextStep(Requisition $requisition, int $nextStageId): void
    {
        // ✅ FIX 1: Only remove the current logged-in user who performed the action 
        // to stop wiping out the entire system's access list!
        if (Auth::check()) {
            DB::connection('porsql')->table('user_stages')
                ->where('user_id', Auth::id())
                ->where('stage_id', $requisition->getOriginal('stage_id'))
                ->delete();
        }

        $targetRole = self::requiredRoleForStageId($nextStageId);

        if ($targetRole !== null) {

            // ✅ FIX 2: Explicitly query the 'pgsql' connection to guarantee users are found
            $usersWithRole = DB::connection('pgsql')
                ->table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('roles.role_name', $targetRole)
                ->pluck('user_roles.user_id')
                ->toArray();

            // Grant active permission rows to the targeted users
            foreach ($usersWithRole as $userId) {
                DB::connection('porsql')->table('user_stages')->updateOrInsert(
                    ['user_id' => $userId, 'stage_id' => $nextStageId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public static function applyRejection(Requisition $requisition): void
    {
        $requisition->update([
            'status_id' => self::rejectedStatusId() ?? $requisition->status_id,
        ]);
    }

    public static function applyCostCenterReview(Requisition $requisition): void
    {
        $requisition->update([
            'status_id' => self::costCenterReviewStatusId() ?? $requisition->status_id,
        ]);
    }

    public static function applyCancellation(Requisition $requisition): void
    {
        $requisition->update([
            'status_id' => self::cancelledStatusId() ?? $requisition->status_id,
        ]);
    }
}
