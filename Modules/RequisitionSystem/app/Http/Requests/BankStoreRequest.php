<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // For updates, ignore the current bank record's ID to prevent validation failure
        $bankId = $this->route('bank') ? $this->route('bank')->id : null;

        return [
            'name' => 'required|string|max:255|unique:banks,name,' . $bankId,
        ];
    }
}