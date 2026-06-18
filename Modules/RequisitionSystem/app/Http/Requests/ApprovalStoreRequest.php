<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requisition_id' => 'required|integer|exists:porsql.requisitions,id',
            'comments'       => 'nullable|string|max:2000',
            'status'         => 'sometimes|string|in:approved,rejected,pending',
            'stage_id'       => 'nullable|integer|exists:porsql.stages,id',
        ];
    }
}
