<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PipelineStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pipeline = $this->route('pipeline');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('porsql.pipelines', 'name')->ignore($pipeline?->id),
            ],
            'stages' => 'sometimes|array',
            'stages.*.id' => 'nullable|integer|exists:porsql.stages,id',
            'stages.*.name' => 'required|string|max:255',
            'stages.*.sequence' => 'required|integer|min:1',
            'stages.*.user_ids' => 'sometimes|array',
            'stages.*.user_ids.*' => 'uuid|exists:pgsql.users,id',
        ];
    }
}
