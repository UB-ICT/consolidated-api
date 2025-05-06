<?php

namespace Modules\PublicSafety\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageRequest extends FormRequest
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
        $method = $this->method();
        if ($method == 'PUT') {

            return [
                'profilePic' => ['nullable'],
                'sender' => ['required'],
                'messageCategoryId' => ['required'],
                'message' => ['required'],
                'images' => ['required'],
                'location' => ['required'],
                'dateSent' => ['required'],
                'isDeleted' => ['required'],
                'type' => ['required'],
                'timestamp' => ['required'],
            ];
        } else {
            return [
                'profilePic' => ['sometimes', 'required'],
                'sender' => ['sometimes', 'required'],
                'messageCategoryId' => ['sometimes', 'required'],
                'message' => ['sometimes', 'required'],
                'images' => ['sometimes', 'required'],
                'location' => ['sometimes', 'required'],
                'dateSent' => ['sometimes', 'required'],
                'isDeleted' => ['sometimes', 'required'],
                'type' => ['sometimes', 'required'],
                'timestamp' => ['sometimes', 'required'],


            ];
        }
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'profile_pic' => $this->profilePic,
            'message_category_id' => $this-> messageCategoryId,
            'sender_id' => $this->senderId,
            'date_sent' => $this->dateSent,
            'is_deleted' => $this->isDeleted,
            'time_stamp' => $this -> timeStamp
        ]);
    }
}
