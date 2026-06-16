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
            'description'      => 'required|string|max:255',
            'quantity'         => 'required|numeric|min:1',
            'line_item_number' => 'required|integer|min:1',
            'unit_cost'        => 'required|numeric|min:0',
            'total'            => 'required|numeric|min:0', // Changed to required since we guarantee it in prepare
            'comments'         => 'nullable|string|max:1000',
            'requisition_id'   => 'required|integer|exists:requisitions,id',
        ];
    }

    public function prepareForValidation(): void
    {
        // Force re-calculation on every single payload pass to stop spoofing
        if ($this->has(['quantity', 'unit_cost'])) {
            $this->merge([
                'total' => (float)$this->quantity * (float)$this->unit_cost,
            ]);
        }
    }
}
