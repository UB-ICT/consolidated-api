<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RequisitionSystem\Models\Supplier;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get the model instance directly from the route parameter
        $supplier = $this->route('supplier');

        return [
            // Core Supplier Data (Now bulletproof against empty SQL strings)
            'name'           => [
                'required',
                'string',
                'max:255',
                Rule::unique('porsql.suppliers', 'name')->ignore($supplier)
            ],
            'contact_person' => 'required|string|max:255',
            'phone_number'   => 'required|string|max:50',

            'email'          => [
                'required',
                'email',
                'max:255',
                Rule::unique('porsql.suppliers', 'email')->ignore($supplier)
            ],
            'TAX'            => [
                'required',
                'string',
                'max:100',
                Rule::unique('porsql.suppliers', 'TAX')->ignore($supplier)
            ],

            'notes'          => 'nullable|string|max:1000',

            'status_id'      => 'nullable|integer',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('TIN') && !$this->filled('TAX')) {
            $this->merge(['TAX' => $this->input('TIN')]);
        }
    }
}
