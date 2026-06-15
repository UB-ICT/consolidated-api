<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequisitionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Capture the current requisition from the route parameter (for unique rule ignores on updates)
        $requisition = $this->route('requisition');

        return [
            // Core Header Columns
            'number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('requisitions', 'number')->ignore($requisition)
            ],
            'cost_center_id'     => 'required|integer|exists:cost_centers,id',
            'status_id'          => 'required|integer|exists:statuses,id',
            'currency_id'        => 'required|integer|exists:currencies,id',
            'conversion_rate_id' => 'required|integer|exists:conversion_rates,id', // Matches your model property
            'total'              => 'nullable|numeric|min:0',
            'stage_id'           => 'nullable|integer|exists:stages,id',

            // Multi-Vendor Sourcing Validation Rules
            'suppliers'                  => 'required|array|min:1', // Must have at least 1 supplier option
            'suppliers.*.supplier_id'    => 'required|integer|exists:suppliers,id', // Must be a valid vendor id
            'suppliers.*.is_recommended' => 'required|boolean', // True/False indicator flag
            'suppliers.*.quoted_total'   => 'nullable|numeric|min:0',
            'suppliers.*.quote_reference_number' => 'nullable|string|max:100',
        ];
    }
}
