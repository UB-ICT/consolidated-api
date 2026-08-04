<?php

namespace Modules\RequisitionSystem\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Supplier;

class PurchaseOrderToSupplierMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Requisition $requisition,
        public readonly Supplier $supplier,
        public readonly ?string $officerMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        $poNumber = $this->requisition->purchase_order_number
            ?: $this->requisition->number;

        return new Envelope(
            subject: sprintf('Purchase Order %s', $poNumber),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'requisitionsystem::emails.purchase-order',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (!$this->requisition->purchase_order_file_path) {
            return [];
        }

        return [
            Attachment::fromStorageDisk(
                'local',
                $this->requisition->purchase_order_file_path
            )->as($this->requisition->purchase_order_file_name ?: 'purchase-order.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
