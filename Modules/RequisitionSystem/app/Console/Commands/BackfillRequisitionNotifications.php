<?php

namespace Modules\RequisitionSystem\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\UserStage;
use Modules\RequisitionSystem\Notifications\RequisitionSubmittedNotification;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;

/**
 * Notifies approvers about requisitions already sitting in their queue that
 * never triggered a notification. Safe to re-run: recipients who already have
 * a notification for a given requisition are skipped.
 */
class BackfillRequisitionNotifications extends Command
{
    protected $signature = 'requisitions:backfill-notifications {--dry-run : Preview without sending anything}';

    protected $description = 'Notify approvers about pending requisitions that never triggered a notification.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $pendingStatusId = RequisitionWorkflow::pendingStatusId();

        if ($pendingStatusId === null) {
            $this->error('Could not resolve the "Pending" status id.');

            return self::FAILURE;
        }

        $requisitions = Requisition::where('status_id', $pendingStatusId)->get();
        $totalSent = 0;
        $totalSkipped = 0;

        foreach ($requisitions as $requisition) {
            if (!$requisition->stage_id) {
                continue;
            }

            $userIds = UserStage::query()
                ->where('stage_id', $requisition->stage_id)
                ->pluck('user_id')
                ->unique()
                ->values();

            if ($userIds->isEmpty()) {
                continue;
            }

            $recipients = User::query()->whereIn('id', $userIds->all())->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            $alreadyNotifiedIds = DB::connection('pgsql')
                ->table('notifications')
                ->whereIn('notifiable_id', $recipients->pluck('id'))
                ->whereRaw("(data::jsonb->>'requisition_id') = ?", [(string) $requisition->id])
                ->pluck('notifiable_id');

            $missingRecipients = $recipients->reject(
                fn (User $recipient) => $alreadyNotifiedIds->contains($recipient->id)
            );

            if ($missingRecipients->isEmpty()) {
                $totalSkipped++;

                continue;
            }

            $this->line(sprintf(
                '%s %s (stage %s): %d recipient(s) [%s]',
                $dryRun ? '[dry-run]' : '[sending]',
                $requisition->number,
                $requisition->stage_id,
                $missingRecipients->count(),
                $missingRecipients->pluck('name')->implode(', ')
            ));

            if (!$dryRun) {
                Notification::send(
                    $missingRecipients,
                    new RequisitionSubmittedNotification($requisition, $requisition->creator)
                );
            }

            $totalSent += $missingRecipients->count();
        }

        $this->info(sprintf(
            '%s%d notification(s) across %d pending requisition(s) (%d already fully notified).',
            $dryRun ? '[dry-run] Would send ' : 'Sent ',
            $totalSent,
            $requisitions->count(),
            $totalSkipped
        ));

        return self::SUCCESS;
    }
}
