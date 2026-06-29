<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RequisitionSystem\Support\RequisitionLogAction;

class RequisitionLogStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => 'required|string|max:2000',
            'action'   => [
                'sometimes',
                'string',
                Rule::in([RequisitionLogAction::COMMENT]),
            ],
        ];
    }
}
