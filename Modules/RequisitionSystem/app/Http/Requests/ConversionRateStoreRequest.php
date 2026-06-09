<?php

// This request file validates that the conversion rate is a valid number (allowing decimals) 
// and that the currency_id provided actually matches an existing ID in your currencies table.

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversionRateStoreRequest extends FormRequest
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
        return [
            'rate'        => 'required|numeric|min:0.000001', // Ensures a positive non-zero decimal rate
            'currency_id' => 'required|integer|exists:currencies,id', // Assumes a currencies table exists
        ];
    }
}
