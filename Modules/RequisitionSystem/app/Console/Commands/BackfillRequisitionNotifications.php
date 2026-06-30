<?php

namespace Modules\RequisitionSystem\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Notifications\RequisitionSubmittedNotification;
use Modules\RequisitionSystem\Support\GuardsRequisitionEditing;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;

/**
 * Notifies approvers about requisitions already sitting in their queue that
 * never triggered a notification - e.g. submitted before the notification
 * feature existed, or missed by a transient send failure. Safe to re-run:
 * recipients who already have a notification for a given requisition are
 * skipped.
 */
class BackfillRequisitionNotifications extends Command
{
    use GuardsRequisitionEditing;

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
            $role = RequisitionWorkflow::requiredRoleForStageId((int) $requisition->stage_id);

            if ($role === null) {
                continue;
            }

            $recipientsQuery = User::whereHas('roles', fn ($query) => $query->where('role_name', $role));

            // Cost-center-scoped roles (e.g. director-dean) only review their own
            // department; global roles (budget-officer, VP, etc.) see everything.
            // user_cost_center lives on the 'porsql' connection (different schema
            // than User's 'pgsql' connection), so it can't be joined via whereHas.
            if (!in_array($role, $this->globalRequisitionRoles(), true)) {
                $costCenterUserIds = DB::connection('porsql')
                    ->table('user_cost_center')
                    ->where('cost_center_id', $requisition->cost_center_id)
                    ->pluck('user_id');

                $recipientsQuery->whereIn('id', $costCenterUserIds);
            }

            $recipients = $recipientsQuery->get();

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
                '%s %s (%s): %d recipient(s) [%s]',
                $dryRun ? '[dry-run]' : '[sending]',
                $requisition->number,
                $role,
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
