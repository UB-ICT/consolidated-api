<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\RequisitionSystem\Support\BudgetLogAction;
use Illuminate\Validation\Rule;

class BudgetLogStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => 'required|string|max:2000',
            'action' => ['sometimes', 'string', Rule::in([BudgetLogAction::COMMENT])],
        ];
    }
}
