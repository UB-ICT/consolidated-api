<?php

namespace Modules\RequisitionSystem\Notifications;

use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Supplier;

class SupplierRequestSubmittedNotification extends Notification
{
    public function __construct(
        private readonly Supplier $supplier,
        private readonly ?User $submitter,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'          => 'supplier_request_submitted',
            'supplier_id'   => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'submitted_by'  => $this->submitter?->name,
            'message'       => sprintf(
                '%s submitted a new supplier request: %s.',
                $this->submitter?->name ?? 'Someone',
                $this->supplier->name
            ),
        ];
    }
}
