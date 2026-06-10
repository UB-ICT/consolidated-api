<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierBankStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'    => 'required|integer|exists:suppliers,id',
            'bank_id'        => 'required|integer|exists:banks,id',
            'account_number' => 'required|string|max:100',
            'account_name'   => 'required|string|max:255',
            'address'        => 'nullable|string|max:500',
        ];
    }
}
