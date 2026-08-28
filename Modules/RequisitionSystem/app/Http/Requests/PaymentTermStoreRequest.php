<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentTermStoreRequest extends FormRequest
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
        // For updates, ignore the current payment term record's ID to prevent validation failure
        $paymentTermId = $this->route('paymentTerm') ? $this->route('paymentTerm')->id : null;

        return [
            'name' => 'required|string|max:255|unique:porsql.payment_terms,name,' . $paymentTermId,
        ];
    }
}
