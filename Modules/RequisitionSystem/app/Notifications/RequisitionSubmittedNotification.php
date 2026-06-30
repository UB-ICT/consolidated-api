<?php

namespace Modules\RequisitionSystem\Notifications;

use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

class RequisitionSubmittedNotification extends Notification
{
    public function __construct(
        private readonly Requisition $requisition,
        private readonly ?User $submitter,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'               => 'requisition_submitted',
            'requisition_id'     => $this->requisition->id,
            'requisition_number' => $this->requisition->number,
            'submitted_by'       => $this->submitter?->name,
            'message'            => sprintf(
                '%s submitted requisition %s for your review.',
                $this->submitter?->name ?? 'A requester',
                $this->requisition->number
            ),
        ];
    }
}
