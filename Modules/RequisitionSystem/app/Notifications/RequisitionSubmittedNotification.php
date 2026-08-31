<?php

namespace Modules\RequisitionSystem\Notifications;

use App\Notifications\Channels\FcmChannel;
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
        return ['database', FcmChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        return $this->notificationPayload();
    }

    /**
     * @return array{title: string, body: string, data: array<string, string>}
     */
    public function toFcm($notifiable): array
    {
        $payload = $this->notificationPayload();

        return [
            'title' => 'Requisition needs review',
            'body'  => $payload['message'],
            'data'  => [
                'type'               => (string) $payload['type'],
                'requisition_id'     => (string) $payload['requisition_id'],
                'requisition_number' => (string) ($payload['requisition_number'] ?? ''),
                'submitted_by'       => (string) ($payload['submitted_by'] ?? ''),
                'stage_id'           => (string) ($payload['stage_id'] ?? ''),
                'message'            => (string) $payload['message'],
                'url'                => sprintf(
                    '/requisitions/forms?requisition=%s',
                    $payload['requisition_id']
                ),
            ],
        ];
    }

    /**
     * @return array{
     *     type: string,
     *     requisition_id: int|string,
     *     requisition_number: string|null,
     *     submitted_by: string|null,
     *     stage_id: int|null,
     *     message: string
     * }
     */
    private function notificationPayload(): array
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
