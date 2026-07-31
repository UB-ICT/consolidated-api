<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PipelineStagesSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stages' => 'required|array',
            'stages.*.id' => 'nullable|integer|exists:porsql.stages,id',
            'stages.*.name' => 'required|string|max:255',
            'stages.*.sequence' => 'required|integer|min:1',
            'stages.*.user_ids' => 'sometimes|array',
            'stages.*.user_ids.*' => 'uuid|exists:pgsql.users,id',
        ];
    }
}
