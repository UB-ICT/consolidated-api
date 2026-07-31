<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RequisitionSystem\Support\BudgetWorkflow;

class BudgetImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:10240'],
            'cost_center_id' => ['required', 'integer', 'exists:porsql.cost_centers,id'],
            'budget_year_id' => ['required', 'integer', 'exists:porsql.budget_years,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => [
                'nullable',
                'string',
                Rule::in(BudgetWorkflow::budgetStatuses()),
            ],
            'sync_accounts' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sync_accounts')) {
            $this->merge([
                'sync_accounts' => filter_var(
                    $this->input('sync_accounts'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) ?? true,
            ]);
        }
    }
}
