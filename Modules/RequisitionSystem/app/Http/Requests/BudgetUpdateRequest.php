<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BudgetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:5000',
            'line_items' => 'sometimes|array',
            'line_items.*.chart_of_account_id' => 'required_with:line_items|integer|exists:porsql.chart_of_accounts,id',
            'line_items.*.amount' => 'required_with:line_items|numeric|min:0',
            'line_items.*.notes' => 'nullable|string|max:2000',
            'submit' => 'sometimes|boolean',
            'activity_comment' => 'nullable|string|max:2000',
        ];
    }
}
