<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Approval;
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

    /**
     * @return array<string, int> role_name => stage_id
     */
    public static function roleStageMapping(): array
    {
        return array_flip(self::STAGE_ROLE_MAP);
    }

    /**
     * The stage a requisition created by this user should start at / return to
     * on rejection. If the creator themselves holds an approver role, that's
     * their own stage (no point routing their own form through stages below
     * their authority); otherwise it's the Draft stage, same as a plain requester.
     */
    public static function originStageIdForUser(User $user, ?int $pipelineId = null): int
    {
        $pipelineId ??= self::defaultPipelineId();
        $roleName = $user->roles()->first()?->role_name;
        $roleStageMap = self::roleStageMapping();

        if ($roleName !== null && isset($roleStageMap[$roleName])) {
            return $roleStageMap[$roleName];
        }

        return self::stageIdForSequence(self::DRAFT_STAGE_SEQUENCE, $pipelineId) ?? Stage::query()->value('id');
    }

    /**
     * Translate an origin stage into a submit-time override: null means "use
     * the standard first-approver stage" (the origin was just the Draft stage,
     * i.e. a plain requester); a stage id means "start there instead".
     */
    public static function startStageIdFromOrigin(?int $originStageId, ?int $pipelineId = null): ?int
    {
        if ($originStageId === null) {
            return null;
        }

        $pipelineId ??= self::defaultPipelineId();
        $draftStageId = self::stageIdForSequence(self::DRAFT_STAGE_SEQUENCE, $pipelineId) ?? Stage::query()->value('id');

        return $originStageId !== $draftStageId ? $originStageId : null;
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

    /**
     * The stage immediately before the given one, or null if it's the first
     * approver stage (in which case the requisition's date_prepared is the
     * relevant "arrival" timestamp instead of a prior stage's approval).
     */
    public static function previousStageIdForStage(int $stageId, ?int $pipelineId = null): ?int
    {
        $pipelineId ??= self::defaultPipelineId();
        $currentSequence = self::sequenceForStageId($stageId, $pipelineId);

        if ($currentSequence === null || $currentSequence <= self::SUBMITTED_STAGE_SEQUENCE) {
            return null;
        }

        return self::stageIdForSequence($currentSequence - 1, $pipelineId);
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

    /**
     * @param int|null $startStageId Override the default first-approver stage,
     *        e.g. when the creator themselves holds an approver role and the
     *        form should start at their own stage instead.
     */
    public static function applySubmitState(array &$data, ?int $pipelineId = null, ?int $startStageId = null): void
    {
        $pipelineId ??= self::defaultPipelineId();
        $submittedStageId = $startStageId
            ?? self::stageIdForSequence(self::SUBMITTED_STAGE_SEQUENCE, $pipelineId)
            ?? Stage::query()->value('id');

        $data['status_id'] = self::pendingStatusId() ?? $data['status_id'] ?? Status::query()->value('id');
        $data['stage_id'] = $submittedStageId;
        $data['current_stage_sequence'] = self::sequenceForStageId($submittedStageId, $pipelineId)
            ?? self::SUBMITTED_STAGE_SEQUENCE;
    }

    public static function applyResubmitFromCostCenterReview(array &$data, Requisition $requisition): void
    {
        $data['status_id'] = self::pendingStatusId() ?? $requisition->status_id;
        $data['stage_id'] = $requisition->stage_id;
        $data['current_stage_sequence'] = $requisition->current_stage_sequence;
    }

    /**
     * After a rejection, the requester edits and resubmits from the Draft
     * stage. Send it back to whichever stage actually rejected it, rather
     * than restarting the whole pipeline from the first approver.
     */
    public static function applyResubmitFromRejection(array &$data, Requisition $requisition, ?int $pipelineId = null): void
    {
        $pipelineId ??= self::defaultPipelineId();

        $rejectingStageId = Approval::where('requisition_id', $requisition->id)
            ->where('status', 'rejected')
            ->orderByDesc('signed_at')
            ->value('stage_id');

        if ($rejectingStageId === null) {
            self::applySubmitState($data, $pipelineId);
            return;
        }

        $data['status_id'] = self::pendingStatusId() ?? $requisition->status_id;
        $data['stage_id'] = (int) $rejectingStageId;
        $data['current_stage_sequence'] = self::sequenceForStageId((int) $rejectingStageId, $pipelineId)
            ?? self::SUBMITTED_STAGE_SEQUENCE;
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

    /**
     * A rejection kicks the requisition back to its creator's home stage for
     * review and resubmission, rather than leaving it parked at whichever
     * approver stage rejected it. For a plain requester that's the Draft
     * stage; for a creator who themselves holds an approver role, it's their
     * own stage.
     */
    public static function applyRejection(Requisition $requisition, ?int $pipelineId = null): void
    {
        $pipelineId ??= self::defaultPipelineId();
        $draftStageId = self::stageIdForSequence(self::DRAFT_STAGE_SEQUENCE, $pipelineId)
            ?? Stage::query()->value('id');
        $returnStageId = $requisition->origin_stage_id ?? $draftStageId;

        $requisition->update([
            'status_id'              => self::rejectedStatusId() ?? $requisition->status_id,
            'stage_id'               => $returnStageId ?? $requisition->stage_id,
            'current_stage_sequence' => self::sequenceForStageId((int) $returnStageId, $pipelineId)
                ?? self::DRAFT_STAGE_SEQUENCE,
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
