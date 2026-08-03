<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierQuickStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255|unique:porsql.suppliers,name',
            'contact_person' => 'nullable|string|max:255',
            'phone_number'   => 'nullable|string|max:50',
            'email'          => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('porsql.suppliers', 'email'),
            ],
            'TAX'            => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('porsql.suppliers', 'TAX'),
            ],
            'notes'          => 'nullable|string|max:1000',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('TIN') && !$this->filled('TAX')) {
            $this->merge(['TAX' => $this->input('TIN')]);
        }
    }
}
