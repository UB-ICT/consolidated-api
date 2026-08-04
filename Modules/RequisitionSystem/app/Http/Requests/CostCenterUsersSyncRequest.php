<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CostCenterUsersSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'present|array',
            'user_ids.*' => 'uuid|exists:pgsql.users,id',
        ];
    }
}
