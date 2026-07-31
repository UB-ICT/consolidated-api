<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Support\RequisitionSupplierQuoteRules;
use Modules\RequisitionSystem\Support\SupplierStatus;

class RequisitionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requisition = $this->route('requisition');

        return [
            'number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('porsql.requisitions', 'number')->ignore($requisition?->id),
            ],
            'purchase_order_number' => 'prohibited',

            'cost_center_id' => 'required|integer|exists:porsql.cost_centers,id',
            'date_prepared' => 'nullable|date',
            'status_id' => [
                Rule::prohibitedIf(fn() => $this->isMethod('POST')),
                'nullable',
                'integer',
                'exists:porsql.statuses,id',
            ],
            'currency_id' => 'nullable|integer|exists:porsql.currencies,id',
            'total' => 'nullable|numeric|min:0',
            'stage_id' => 'nullable|integer|exists:porsql.stages,id',

            'priority' => 'required|string|in:routine,standard,urgent,critical',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',

            'is_recurring' => 'required|boolean',
            'reminder_date' => 'nullable|required_if:is_recurring,true|date|after_or_equal:today',
            'current_stage_sequence' => 'nullable|integer|min:1',

            'suppliers' => 'nullable|array',
            'suppliers.*.supplier_id' => 'required|integer|exists:porsql.suppliers,id',
            'suppliers.*.is_recommended' => 'required|boolean',
            'suppliers.*.quoted_total' => 'nullable|numeric|min:0',
            'suppliers.*.quote_reference_number' => 'nullable|string|max:100',

            'items' => 'required|array|min:1',
            'items.*.chart_of_account_id' => 'required|integer|exists:porsql.chart_of_accounts,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.comments' => 'nullable|string|max:1000',

            'submit' => 'sometimes|boolean',

            'activity_comment' => [
                Rule::prohibitedIf(fn() => $this->isMethod('POST')),
                'nullable',
                'string',
                'max:2000',
            ],

            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'integer|exists:porsql.tags,id',
        ];
    }

    public function shouldSubmit(): bool
    {
        return $this->boolean('submit');
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $costCenterId = (int) $this->input('cost_center_id');
            $tagIds = collect($this->input('tag_ids', []))->filter()->unique()->values();

            if ($tagIds->isNotEmpty() && $costCenterId) {
                $invalidCount = \Modules\RequisitionSystem\Models\Tag::query()
                    ->whereIn('id', $tagIds)
                    ->where('cost_center_id', '!=', $costCenterId)
                    ->count();

                if ($invalidCount > 0) {
                    $validator->errors()->add(
                        'tag_ids',
                        'All tags must belong to the requisition cost center.'
                    );
                }
            }

            $itemAccountIds = collect($this->input('items', []))
                ->pluck('chart_of_account_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($costCenterId && $itemAccountIds->isNotEmpty()) {
                $activeBudget = Budget::query()
                    ->operational()
                    ->where('cost_center_id', $costCenterId)
                    ->with('lineItems')
                    ->first();

                if (!$activeBudget) {
                    $validator->errors()->add(
                        'items',
                        'There is no active budget for this cost center. Line items must come from an active budget.'
                    );
                } else {
                    $allowedAccountIds = $activeBudget->lineItems
                        ->pluck('chart_of_account_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    foreach ($this->input('items', []) as $index => $item) {
                        $accountId = (int) ($item['chart_of_account_id'] ?? 0);

                        if ($accountId && !in_array($accountId, $allowedAccountIds, true)) {
                            $validator->errors()->add(
                                "items.{$index}.chart_of_account_id",
                                'Selected account is not on the active budget for this cost center.'
                            );
                        }
                    }
                }
            }

            if (!$this->shouldSubmit()) {
                return;
            }

            $total = RequisitionSupplierQuoteRules::calculateItemsTotal(
                $this->input('items', [])
            );

            $errors = RequisitionSupplierQuoteRules::validateSuppliers(
                $this->input('suppliers', []),
                $total
            );

            foreach ($errors as $field => $message) {
                $validator->errors()->add($field, $message);
            }

            $excludedSupplierIds = Supplier::query()
                ->whereIn(
                    'status_id',
                    Status::query()
                        ->whereIn('name', [SupplierStatus::DELETED, SupplierStatus::REJECTED])
                        ->pluck('id')
                )
                ->pluck('id');

            foreach ($this->input('suppliers', []) as $index => $supplier) {
                $supplierId = $supplier['supplier_id'] ?? null;

                if (!$supplierId || !$excludedSupplierIds->contains((int) $supplierId)) {
                    continue;
                }

                $supplierModel = Supplier::query()
                    ->with('status')
                    ->find($supplierId);

                $message = $supplierModel?->isRejected()
                    ? 'The selected supplier has been rejected.'
                    : 'The selected supplier has been deleted.';

                $validator->errors()->add(
                    "suppliers.{$index}.supplier_id",
                    $message
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $suppliers = $this->input('suppliers');

        if (!is_array($suppliers)) {
            return;
        }

        $this->merge([
            'suppliers' => RequisitionSupplierQuoteRules::normalizeRecommendedSupplier(
                $suppliers
            ),
        ]);
    }
}
