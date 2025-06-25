<?php

namespace Modules\PublicSafety\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'guard_name' => ['required'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => $this->name,
            'guard_name' => $this->guardName,
        ]);
    }
}