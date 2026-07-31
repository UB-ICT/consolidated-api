<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\RequisitionSystem\Models\ChartOfAccount;

class ChartOfAccountStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $chartOfAccount = $this->route('chartOfAccount')
            ?? $this->route('chart_of_account');

        return [
            'account_no' => [
                'required',
                'string',
                'max:20',
                Rule::unique('porsql.chart_of_accounts', 'account_no')->ignore($chartOfAccount?->id),
            ],
            'description' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('porsql.chart_of_accounts', 'id'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parentId = $this->input('parent_id');

            if ($parentId === null || $parentId === '') {
                return;
            }

            $parentId = (int) $parentId;
            /** @var ChartOfAccount|null $chartOfAccount */
            $chartOfAccount = $this->route('chartOfAccount')
                ?? $this->route('chart_of_account');

            if ($chartOfAccount && $parentId === (int) $chartOfAccount->id) {
                $validator->errors()->add('parent_id', 'An account cannot be its own parent.');

                return;
            }

            if ($chartOfAccount && in_array($parentId, $chartOfAccount->descendantIds(), true)) {
                $validator->errors()->add(
                    'parent_id',
                    'An account cannot be nested under one of its child accounts.'
                );
            }
        });
    }
}
