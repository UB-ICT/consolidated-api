<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => 'nullable|string|max:1000',
        ];
    }
}
