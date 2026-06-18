<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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

            'cost_center_id' => 'required|integer|exists:porsql.cost_centers,id',
            'date_prepared'  => 'nullable|date',
            'status_id'      => [
                Rule::prohibitedIf(fn () => $this->isMethod('POST')),
                'nullable',
                'integer',
                'exists:porsql.statuses,id',
            ],
            'currency_id'    => 'nullable|integer|exists:porsql.currencies,id',
            'total'          => 'nullable|numeric|min:0',
            'stage_id'       => 'nullable|integer|exists:porsql.stages,id',

            'priority'               => 'required|string|in:routine,standard,urgent,critical',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',

            'is_recurring'  => 'required|boolean',
            'reminder_date' => 'nullable|required_if:is_recurring,true|date|after_or_equal:today',

            'suppliers'                          => 'nullable|array',
            'suppliers.*.supplier_id'            => 'required|integer|exists:porsql.suppliers,id',
            'suppliers.*.is_recommended'         => 'required|boolean',
            'suppliers.*.quoted_total'           => 'nullable|numeric|min:0',
            'suppliers.*.quote_reference_number' => 'nullable|string|max:100',

            'items'                      => 'required|array|min:1',
            'items.*.line_item_number'   => 'required|string|max:50',
            'items.*.description'        => 'required|string|max:255',
            'items.*.quantity'    => 'required|numeric|min:1',
            'items.*.unit_cost'   => 'required|numeric|min:0',
            'items.*.comments'    => 'nullable|string|max:1000',

            'activity_comment' => [
                Rule::prohibitedIf(fn () => $this->isMethod('POST')),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
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

            $deletedStatusId = Status::where('name', SupplierStatus::DELETED)->value('id');

            if (!$deletedStatusId) {
                return;
            }

            $deletedSupplierIds = Supplier::query()
                ->where('status_id', $deletedStatusId)
                ->pluck('id');

            foreach ($this->input('suppliers', []) as $index => $supplier) {
                $supplierId = $supplier['supplier_id'] ?? null;

                if ($supplierId && $deletedSupplierIds->contains((int) $supplierId)) {
                    $validator->errors()->add(
                        "suppliers.{$index}.supplier_id",
                        'The selected supplier has been deleted.'
                    );
                }
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
