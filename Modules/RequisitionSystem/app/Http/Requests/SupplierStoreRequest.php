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
                Rule::unique('suppliers', 'name')->ignore($supplier)
            ],
            'contact_person' => 'required|string|max:255',
            'phone_number'   => 'required|string|max:50',

            'email'          => [
                'required',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($supplier)
            ],
            'TIN'            => [
                'required',
                'string',
                'max:100',
                Rule::unique('suppliers', 'TIN')->ignore($supplier)
            ],

            'notes'          => 'nullable|string|max:1000',

            'status_id'      => 'nullable|integer',

            // Banking Data Fields
            'bank_id'        => 'required|exists:banks,id',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:500',
        ];
    }
}
