<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetYearStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $budgetYear = $this->route('budget_year');

        return [
            'label' => [
                'required',
                'string',
                'max:20',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('porsql.budget_years', 'label')->ignore($budgetYear?->id),
            ],
            'submissions_open' => 'sometimes|boolean',
        ];
    }
}
