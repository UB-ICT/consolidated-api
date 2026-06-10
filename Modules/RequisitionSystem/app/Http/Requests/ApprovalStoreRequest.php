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
            'requisition_id' => 'required|integer|exists:requisitions,id', // Assumes a requisitions table exists
            'user_id'        => 'required|integer|exists:users,id',        // Assumes a users table exists
            'comments'       => 'nullable|string|max:1000',
            'status'         => 'sometimes|string|in:approved,rejected,pending', // Optional, standard for approvals
        ];
    }
}
