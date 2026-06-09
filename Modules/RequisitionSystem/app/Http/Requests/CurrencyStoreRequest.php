<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurrencyStoreRequest extends FormRequest
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
        $currencyId = $this->route('currency') ? $this->route('currency')->id : null;

        return [
            'name'   => 'required|string|max:255|unique:currencies,name,' . $currencyId,
            'symbol' => 'required|string|max:10', // e.g., $, €, £, ¥
        ];
    }
}
