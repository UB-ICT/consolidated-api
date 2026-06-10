<?php

namespace Modules\RequisitionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PipelineStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:pipelines,name',
        ];
    }
}
