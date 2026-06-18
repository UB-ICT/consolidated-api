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
                'nullable',
                'string',
                'max:255',
                Rule::unique('porsql.requisitions', 'number')->ignore($requisitionId),
            ],
        
            'cost_center_id'     => 'required|integer|exists:porsql.cost_centers,id',
            'supplier_id'        => 'nullable|integer|exists:porsql.suppliers,id',
            'date_prepared'      => 'nullable|date',
            'status_id'          => 'nullable|integer|exists:porsql.statuses,id',
            'currency_id'        => 'nullable|integer|exists:porsql.currencies,id',
            'conversion_rate_id' => 'nullable|integer|exists:porsql.conversion_rates,id',
            'total'              => 'nullable|numeric|min:0',
            'stage_id'           => 'nullable|integer|exists:porsql.stages,id',
        
            'priority'               => 'required|string|in:low,medium,high',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
        
            // Recurring Requisition Fields
            'is_recurring'    => 'required|boolean',
            'reminder_date'   => 'nullable|required_if:is_recurring,true|date|after_or_equal:today',
            'expiration_date' => 'nullable|required_if:is_recurring,true|date|after:reminder_date',
        
            // Multi-Vendor Sourcing Validation Rules
            'suppliers'                          => 'required|array|min:1',
            'suppliers.*.supplier_id'            => 'required|integer|exists:porsql.suppliers,id',
            'suppliers.*.is_recommended'         => 'required|boolean',
            'suppliers.*.quoted_total'           => 'nullable|numeric|min:0',
            'suppliers.*.quote_reference_number' => 'nullable|string|max:100',
        
            // Requisition Items
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity'    => 'required|numeric|min:1',
            'items.*.unit_cost'   => 'required|numeric|min:0',
            'items.*.comments'    => 'nullable|string|max:1000',
        ];
    }
}
