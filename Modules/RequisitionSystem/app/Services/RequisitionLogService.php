<?php

namespace Modules\RequisitionSystem\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Logs;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Support\RequisitionLogAction;

class RequisitionLogService
{
    public function record(
        Requisition $requisition,
        User $user,
        string $action,
        ?string $summary = null,
        ?string $comments = null,
        ?string $fileName = null,
        ?string $filePath = null
    ): Logs {
        return Logs::create([
            'requisition_id' => $requisition->id,
            'user_id'        => $user->id,
            'action'         => $action,
            'summary'        => $summary,
            'comments'       => $comments,
            'file_name'      => $fileName,
            'file_path'      => $filePath,
        ]);
    }

    public function recordCreation(Requisition $requisition, User $user): Logs
    {
        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::CREATED,
            sprintf('Requisition %s created.', $requisition->number)
        );
    }

    public function recordUpdate(
        Requisition $requisition,
        User $user,
        Requisition $before,
        array $validated,
        Collection $previousItems,
        ?string $comments = null
    ): ?Logs {
        $summaryParts = $this->buildChangeSummary($before, $validated, $previousItems);

        if (empty($summaryParts) && !$comments) {
            return null;
        }

        $summary = empty($summaryParts)
            ? 'Requisition updated.'
            : 'Updated: ' . implode('; ', $summaryParts) . '.';

        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::UPDATED,
            $summary,
            $comments
        );
    }

    public function summarizeChanges(
        Requisition $before,
        array $validated,
        Collection $previousItems
    ): ?string {
        $summaryParts = $this->buildChangeSummary($before, $validated, $previousItems);

        if (empty($summaryParts)) {
            return null;
        }

        return 'Updated: ' . implode('; ', $summaryParts) . '.';
    }

    public function recordSubmission(
        Requisition $requisition,
        User $user,
        ?string $comments = null,
        ?string $summary = null
    ): Logs {
        $defaultSummary = sprintf(
            'Requisition %s submitted for review.',
            $requisition->number
        );

        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::SUBMITTED,
            $summary ? "{$defaultSummary} {$summary}" : $defaultSummary,
            $comments
        );
    }

    public function recordApprovalDecision(
        Requisition $requisition,
        User $user,
        string $action,
        ?string $comments = null,
        ?string $stageName = null
    ): Logs {
        $prefix = $action === RequisitionLogAction::APPROVED ? 'Approved' : 'Rejected';
        $summary = $stageName
            ? sprintf('%s at %s stage.', $prefix, $stageName)
            : sprintf('Requisition %s.', strtolower($prefix));

        return $this->record($requisition, $user, $action, $summary, $comments);
    }

    public function recordCostCenterReviewRequest(
        Requisition $requisition,
        User $user,
        ?string $comments = null,
        ?string $stageName = null
    ): Logs {
        $summary = $stageName
            ? sprintf('Sent back to cost center for review from %s stage.', $stageName)
            : 'Sent back to cost center for review.';

        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::COST_CENTER_REVIEW,
            $summary,
            $comments
        );
    }

    public function recordCancellation(
        Requisition $requisition,
        User $user,
        ?string $comments = null
    ): Logs {
        $summary = sprintf(
            'Requisition %s cancelled.',
            $requisition->number
        );

        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::CANCELLED,
            $summary,
            $comments
        );
    }

    public function recordClosure(
        Requisition $requisition,
        User $user,
        ?string $comments = null
    ): Logs {
        $summary = sprintf(
            'Requisition %s closed (discontinued / not processed).',
            $requisition->number
        );

        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::CLOSED,
            $summary,
            $comments
        );
    }

    public function recordComment(
        Requisition $requisition,
        User $user,
        string $comments,
        ?string $fileName = null,
        ?string $filePath = null
    ): Logs {
        $summary = $fileName
            ? sprintf('Comment added with supporting document (%s).', $fileName)
            : 'Comment added.';

        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::COMMENT,
            $summary,
            $comments,
            $fileName,
            $filePath
        );
    }

    public function recordPurchaseOrderNumberUpdate(
        Requisition $requisition,
        User $user,
        ?string $purchaseOrderNumber,
        ?string $previousValue = null
    ): Logs {
        if ($purchaseOrderNumber) {
            $summary = sprintf(
                'Purchase order number set to %s.',
                $purchaseOrderNumber
            );
        } else {
            $summary = $previousValue
                ? sprintf('Purchase order number %s removed.', $previousValue)
                : 'Purchase order number cleared.';
        }

        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::UPDATED,
            $summary
        );
    }

    public function recordPurchaseOrderDocumentUpload(
        Requisition $requisition,
        User $user,
        string $fileName
    ): Logs {
        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::UPDATED,
            sprintf('Purchase order document uploaded (%s).', $fileName)
        );
    }

    public function recordPurchaseOrderEmailed(
        Requisition $requisition,
        User $user,
        string $supplierEmail
    ): Logs {
        return $this->record(
            $requisition,
            $user,
            RequisitionLogAction::UPDATED,
            sprintf('Purchase order emailed to %s.', $supplierEmail)
        );
    }

    private function buildChangeSummary(
        Requisition $before,
        array $validated,
        Collection $previousItems
    ): array {
        $changes = [];

        $fieldLabels = [
            'priority'               => 'Priority',
            'description'            => 'Description',
            'expected_delivery_date' => 'Expected delivery date',
            'is_recurring'           => 'Recurring flag',
            'requires_downpayment'   => '50% downpayment required',
            'reminder_date'          => 'Reminder date',
            'currency_id'            => 'Currency',
            'cost_center_id'         => 'Cost center',
        ];

        foreach ($fieldLabels as $field => $label) {
            if (!array_key_exists($field, $validated)) {
                continue;
            }

            $beforeValue = $before->{$field};
            $afterValue = $validated[$field];

            if ($field === 'currency_id' && (int) $beforeValue !== (int) $afterValue) {
                $currencyName = Currency::find($afterValue)?->name ?? $afterValue;
                $changes[] = "{$label} set to {$currencyName}";
                continue;
            }

            if ((string) $beforeValue !== (string) $afterValue) {
                $displayValue = is_bool($afterValue)
                    ? ($afterValue ? 'yes' : 'no')
                    : (string) $afterValue;
                $changes[] = "{$label} changed to {$displayValue}";
            }
        }

        $newItems = collect($validated['items'] ?? []);
        if ($this->itemsChanged($previousItems, $newItems)) {
            $changes[] = sprintf(
                'Line items updated (%d item%s)',
                $newItems->count(),
                $newItems->count() === 1 ? '' : 's'
            );
        }

        $newSuppliers = collect($validated['suppliers'] ?? []);
        if ($newSuppliers->isNotEmpty()) {
            $changes[] = sprintf(
                'Supplier quotes updated (%d supplier%s)',
                $newSuppliers->count(),
                $newSuppliers->count() === 1 ? '' : 's'
            );
        }

        $newTotal = $newItems->sum(
            fn (array $item) => ($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0)
        );

        if ((float) $before->total !== (float) $newTotal) {
            $changes[] = sprintf('Total changed to %s', number_format($newTotal, 2));
        }

        return array_values(array_unique($changes));
    }

    private function itemsChanged(Collection $previousItems, Collection $newItems): bool
    {
        if ($previousItems->count() !== $newItems->count()) {
            return true;
        }

        $previous = $previousItems
            ->map(fn ($item) => [
                'chart_of_account_id' => (int) $item->chart_of_account_id,
                'quantity'            => round((float) $item->quantity, 4),
                'unit_cost'           => (float) $item->unit_cost,
                'gst_applicable'      => (bool) ($item->gst_applicable ?? false),
                'comments'            => $item->comments,
            ])
            ->values()
            ->all();

        $next = $newItems
            ->map(fn (array $item) => [
                'chart_of_account_id' => (int) ($item['chart_of_account_id'] ?? 0),
                'quantity'            => round((float) ($item['quantity'] ?? 0), 4),
                'unit_cost'           => (float) ($item['unit_cost'] ?? 0),
                'gst_applicable'      => (bool) ($item['gst_applicable'] ?? false),
                'comments'            => $item['comments'] ?? null,
            ])
            ->values()
            ->all();

        return $previous !== $next;
    }
}
