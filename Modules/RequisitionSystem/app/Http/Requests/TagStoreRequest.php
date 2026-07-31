<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tag = $this->route('tag');
        $costCenterId = $this->input('cost_center_id') ?? $tag?->cost_center_id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('porsql.tags', 'name')
                    ->where(fn ($query) => $query->where('cost_center_id', $costCenterId))
                    ->ignore($tag?->id),
            ],
            'cost_center_id' => [
                Rule::requiredIf(fn () => $this->isMethod('POST')),
                'nullable',
                'integer',
                'exists:porsql.cost_centers,id',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/', ' ', $this->input('name')) ?? ''),
            ]);
        }
    }
}
