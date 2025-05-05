<?php

namespace Modules\Message\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreMessageRequest extends FormRequest
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
            'profilePic' => ['nullable'],
            'sender' => ['required'],
            'messageCategoryId' => ['nullable'],
            'images' => ['nullable'],
            'message' => ['required'],
            'location' => ['nullable'],
            'dateSent' => ['required'],
            'isDeleted' => ['required'],
            'type' => ['required'],
        ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'profile_pic' => $this->profilePic,
            'message_category_id' => $this->messageCategoryId,
            'date_sent' => $this->dateSent,
            'is_deleted' => $this->isDeleted,
        ]);
    }
}
