<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255|unique:suppliers,name',
            'contact_person' => 'nullable|string|max:255',
            'phone_number'   => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'TIN'           => 'nullable|string|max:100',
        ];
    }
}
