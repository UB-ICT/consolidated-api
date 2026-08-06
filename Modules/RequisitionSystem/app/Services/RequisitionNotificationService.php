<?php

namespace Modules\RequisitionSystem\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\UserStage;
use Modules\RequisitionSystem\Notifications\RequisitionSubmittedNotification;

final class RequisitionNotificationService
{
    /**
     * Notify users assigned to the requisition's current stage that it needs review.
     * Skips the acting user (submitter / approver) when provided.
     */
    public function notifyCurrentStageReviewers(
        Requisition $requisition,
        ?User $actor = null
    ): void {
        $requisition->loadMissing('status');

        if ($requisition->status?->name !== 'Pending' || !$requisition->stage_id) {
            return;
        }

        $userIds = UserStage::query()
            ->where('stage_id', $requisition->stage_id)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $recipients = User::query()
            ->whereIn('id', $userIds->all())
            ->get()
            ->reject(fn (User $user) => $actor && (string) $user->id === (string) $actor->id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new RequisitionSubmittedNotification($requisition, $actor)
        );
    }

    /**
     * After approval advances the workflow, notify the next stage when still pending.
     */
    public function notifyAfterStageAdvance(
        Requisition $requisition,
        ?User $actor = null
    ): void {
        $requisition->refresh()->loadMissing('status');

        if ($requisition->status?->name !== 'Pending') {
            return;
        }

        $this->notifyCurrentStageReviewers($requisition, $actor);
    }
}
