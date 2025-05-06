<?php

namespace Modules\PublicSafety\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'username' => ['required'],
            'email' => ['required'],
            'picture' => ['nullable'],
            'password' => ['required'],
            'roleId' => ['required'],
            'campusId' => ['required'],
            'userStatusId' => ['required'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'role_id' => $this->roleId,
            'campus_id' => $this->campusId,
            'user_status_id' => $this->userStatusId
        ]);
    }
}