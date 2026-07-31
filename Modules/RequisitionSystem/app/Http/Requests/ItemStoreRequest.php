<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chart_of_account_id' => 'required|integer|exists:porsql.chart_of_accounts,id',
            'quantity'            => 'required|numeric|min:1',
            'unit_cost'           => 'required|numeric|min:0',
            'total'               => 'required|numeric|min:0',
            'comments'            => 'nullable|string|max:1000',
            'requisition_id'      => 'required|integer|exists:porsql.requisitions,id',
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has(['quantity', 'unit_cost'])) {
            $this->merge([
                'total' => (float) $this->quantity * (float) $this->unit_cost,
            ]);
        }
    }
}
