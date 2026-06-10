<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequisitionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number'           => 'required|string|max:255|unique:requisitions,number',
            'cost_center_id'   => 'required|integer|exists:cost_centers,id',
            'supplier_id'      => 'required|integer|exists:suppliers,id',
            'status_id'        => 'required|integer|exists:statuses,id',
            'currency_id'      => 'required|integer|exists:currencies,id',
            'conversion_rate'  => 'nullable|numeric|min:0',
            'total'            => 'nullable|numeric|min:0',
            'stage_id'         => 'nullable|integer|exists:stages,id',
        ];
    }
}
