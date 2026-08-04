<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CostCenterStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $costCenter = $this->route('costCenter') ?? $this->route('cost_center');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('porsql.cost_centers', 'name')->ignore($costCenter?->id),
            ],
            'number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('porsql.cost_centers', 'number')->ignore($costCenter?->id),
            ],
        ];
    }
}
