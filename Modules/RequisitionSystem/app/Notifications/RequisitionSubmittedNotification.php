<?php

namespace Modules\RequisitionSystem\Notifications;

use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

class RequisitionSubmittedNotification extends Notification
{
    public function __construct(
        private readonly Requisition $requisition,
        private readonly ?User $actor,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $actorName = $this->actor?->name ?? 'A requester';

        return [
            'type'               => 'requisition_pending_review',
            'requisition_id'     => $this->requisition->id,
            'requisition_number' => $this->requisition->number,
            'submitted_by'       => $this->actor?->name,
            'stage_id'           => $this->requisition->stage_id,
            'message'            => sprintf(
                '%s sent requisition %s for your review.',
                $actorName,
                $this->requisition->number
            ),
        ];
    }
}
