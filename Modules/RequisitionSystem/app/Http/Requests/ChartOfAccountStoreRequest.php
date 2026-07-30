<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChartOfAccountStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $chartOfAccount = $this->route('chart_of_account');

        return [
            'account_no' => [
                'required',
                'string',
                'max:20',
                Rule::unique('porsql.chart_of_accounts', 'account_no')->ignore($chartOfAccount?->id),
            ],
            'description' => 'required|string|max:255',
        ];
    }
}
