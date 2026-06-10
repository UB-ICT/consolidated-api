<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'  => 'required|integer|exists:users,id',
            'stage_id' => 'required|integer|exists:stages,id',
        ];
    }
}