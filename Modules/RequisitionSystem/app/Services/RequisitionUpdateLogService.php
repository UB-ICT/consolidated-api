<?php

namespace Modules\RequisitionSystem\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Item;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\RequisitionUpdateLog;
use Modules\RequisitionSystem\Models\Supplier;

class RequisitionUpdateLogService
{
    /**
     * @param  list<int>  $previousTagIds
     * @param  list<int>  $afterTagIds
     */
    public function recordFormUpdate(
        Requisition $before,
        Requisition $after,
        User $user,
        Collection $previousItems,
        Collection $afterItems,
        array $validated,
        bool $submitted,
        ?string $activityComment = null,
        array $previousTagIds = [],
        array $afterTagIds = []
    ): ?RequisitionUpdateLog {
        $changes = $this->buildChanges(
            $before,
            $after,
            $previousItems,
            $afterItems,
            $validated,
            $previousTagIds,
            $afterTagIds
        );

        if ($changes === []) {
            return null;
        }

        $summary = $this->buildSummary($changes, $submitted);

        return RequisitionUpdateLog::create([
            'requisition_id'   => $after->id,
            'user_id'          => $user->id,
            'submitted'        => $submitted,
            'event'            => $submitted ? 'submitted' : 'updated',
            'summary'          => $summary,
            'changes'          => $changes,
            'activity_comment' => $activityComment,
        ]);
    }

    public function recordFormCreation(
        Requisition $requisition,
        User $user,
        array $validated,
        Collection $afterItems,
        bool $submitted,
        array $afterTagIds = []
    ): RequisitionUpdateLog {
        $after = $requisition->loadMissing('status');

        $changes = $this->buildChanges(
            new Requisition(['total' => 0]),
            $after,
            collect(),
            $afterItems,
            $validated,
            [],
            $afterTagIds
        );

        if ($changes === []) {
            $changes = [
                'meta' => [
                    'after' => [
                        'requisition_number' => $after->number,
                        'status'             => $after->status?->name,
                    ],
                ],
            ];
        }

        return RequisitionUpdateLog::create([
            'requisition_id'   => $after->id,
            'user_id'          => $user->id,
            'submitted'        => $submitted,
            'event'            => $submitted ? 'submitted' : 'created',
            'summary'          => $submitted
                ? sprintf('Requisition %s created and submitted.', $after->number)
                : sprintf('Requisition %s created as draft.', $after->number),
            'changes'          => $changes,
            'activity_comment' => null,
        ]);
    }

    /**
     * @param  list<int>  $previousTagIds
     * @param  list<int>  $afterTagIds
     * @return array<string, mixed>
     */
    private function buildChanges(
        Requisition $before,
        Requisition $after,
        Collection $previousItems,
        Collection $afterItems,
        array $validated,
        array $previousTagIds,
        array $afterTagIds
    ): array {
        $changes = [];

        foreach ($this->trackedFields() as $field => $label) {
            if (!array_key_exists($field, $validated) && !$before->exists) {
                continue;
            }

            $beforeValue = $before->{$field} ?? null;
            $afterValue = $after->{$field};

            if ($this->valuesEqual($beforeValue, $afterValue)) {
                continue;
            }

            $changes['fields'][$field] = [
                'label'  => $label,
                'before' => $this->normalizeFieldValue($field, $beforeValue),
                'after'  => $this->normalizeFieldValue($field, $afterValue),
            ];
        }

        if ($this->itemsChanged($previousItems, $afterItems)) {
            $changes['line_items'] = [
                'before' => $this->serializeItems($previousItems),
                'after'  => $this->serializeItems($afterItems),
            ];
        }

        $previousSuppliers = $this->serializeSuppliersFromPivot($before);
        $afterSuppliers = $this->serializeSuppliersFromPivot($after);

        if ($previousSuppliers !== $afterSuppliers) {
            $changes['suppliers'] = [
                'before' => $previousSuppliers,
                'after'  => $afterSuppliers,
            ];
        }

        sort($previousTagIds);
        sort($afterTagIds);

        if ($previousTagIds !== $afterTagIds) {
            $changes['tags'] = [
                'before' => $previousTagIds,
                'after'  => $afterTagIds,
            ];
        }

        if (array_key_exists('quote_waiver_reason', $validated)) {
            $beforeReason = $before->quote_waiver_reason;
            $afterReason = $after->quote_waiver_reason;

            if ((string) $beforeReason !== (string) $afterReason) {
                $changes['fields']['quote_waiver_reason'] = [
                    'label'  => 'Quote waiver reason',
                    'before' => $beforeReason,
                    'after'  => $afterReason,
                ];
            }
        }

        $beforeTotal = round((float) $before->total, 2);
        $afterTotal = round((float) $after->total, 2);

        if ($beforeTotal !== $afterTotal) {
            $changes['total'] = [
                'before' => $beforeTotal,
                'after'  => $afterTotal,
            ];
        }

        return $changes;
    }

    /**
     * @return array<string, string>
     */
    private function trackedFields(): array
    {
        return [
            'priority'               => 'Priority',
            'description'            => 'Description',
            'expected_delivery_date' => 'Expected delivery date',
            'is_recurring'           => 'Recurring flag',
            'requires_downpayment'   => '50% downpayment required',
            'reminder_date'          => 'Reminder date',
            'currency_id'            => 'Currency',
            'cost_center_id'         => 'Cost center',
        ];
    }

    private function normalizeFieldValue(string $field, mixed $value): mixed
    {
        if ($field === 'currency_id' && $value) {
            return [
                'id'   => (int) $value,
                'name' => Currency::find($value)?->name,
            ];
        }

        if (is_bool($value)) {
            return $value;
        }

        return $value;
    }

    private function valuesEqual(mixed $before, mixed $after): bool
    {
        if ($before instanceof \DateTimeInterface) {
            $before = $before->format('Y-m-d');
        }

        if ($after instanceof \DateTimeInterface) {
            $after = $after->format('Y-m-d');
        }

        return (string) $before === (string) $after;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializeItems(Collection $items): array
    {
        return $items
            ->map(fn (Item $item) => [
                'id'                  => $item->id,
                'chart_of_account_id' => $item->chart_of_account_id,
                'account_no'          => $item->chartOfAccount?->account_no,
                'description'         => $item->chartOfAccount?->description,
                'quantity'            => round((float) $item->quantity, 4),
                'unit_cost'           => round((float) $item->unit_cost, 2),
                'subtotal'            => round((float) $item->subtotal, 2),
                'gst_applicable'      => (bool) $item->gst_applicable,
                'gst_amount'          => round((float) $item->gst_amount, 2),
                'total'               => round((float) $item->total, 2),
                'comments'            => $item->comments,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializeSuppliersFromPivot(Requisition $requisition): array
    {
        $requisition->loadMissing('suppliers');

        return $requisition->suppliers
            ->map(fn (Supplier $supplier) => [
                'supplier_id'            => $supplier->id,
                'name'                   => $supplier->name,
                'is_recommended'         => (bool) ($supplier->pivot?->is_recommended ?? false),
                'quoted_total'           => $supplier->pivot?->quoted_total,
                'quote_reference_number' => $supplier->pivot?->quote_reference_number,
            ])
            ->sortBy('supplier_id')
            ->values()
            ->all();
    }

    private function itemsChanged(Collection $previousItems, Collection $afterItems): bool
    {
        return $this->serializeItems($previousItems) !== $this->serializeItems($afterItems);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function buildSummary(array $changes, bool $submitted): string
    {
        $parts = [];

        foreach ($changes['fields'] ?? [] as $change) {
            $label = $change['label'] ?? 'Field';
            $after = $change['after'];

            if (is_array($after) && array_key_exists('name', $after)) {
                $parts[] = sprintf('%s set to %s', $label, $after['name'] ?? $after['id']);
                continue;
            }

            if (is_bool($after)) {
                $parts[] = sprintf('%s set to %s', $label, $after ? 'yes' : 'no');
                continue;
            }

            $parts[] = sprintf('%s changed to %s', $label, (string) $after);
        }

        if (isset($changes['line_items'])) {
            $afterCount = count($changes['line_items']['after'] ?? []);
            $beforeCount = count($changes['line_items']['before'] ?? []);
            $parts[] = sprintf(
                'Line items changed (%d → %d item%s)',
                $beforeCount,
                $afterCount,
                $afterCount === 1 ? '' : 's'
            );
        }

        if (isset($changes['suppliers'])) {
            $afterCount = count($changes['suppliers']['after'] ?? []);
            $parts[] = sprintf(
                'Supplier quotes changed (%d supplier%s)',
                $afterCount,
                $afterCount === 1 ? '' : 's'
            );
        }

        if (isset($changes['tags'])) {
            $parts[] = 'Tags updated';
        }

        if (isset($changes['total'])) {
            $parts[] = sprintf(
                'Total changed from %s to %s',
                number_format((float) $changes['total']['before'], 2),
                number_format((float) $changes['total']['after'], 2)
            );
        }

        if ($parts === []) {
            return $submitted ? 'Requisition submitted.' : 'Requisition updated.';
        }

        $prefix = $submitted ? 'Submitted with changes' : 'Updated';

        return $prefix . ': ' . implode('; ', $parts) . '.';
    }
}
