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
            'chat_id' => 'required|exists:chats,id',
            'content' => 'required_without:attachments|string|nullable',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,pdf,doc,docx,txt|max:10240',
            'type' => [
                'required',
                Rule::in(['text', 'image', 'video', 'document', 'audio', 'email', 'sms', 'notification'])
            ],
        ];
    }
    // protected function prepareForValidation()
    // {
    //     $this->merge([

    //         // 'message_category_id' => $this->messageCategoryId,
    //         'sender_id' => $this->senderId,
    //         'is_archive' => $this->isArchive,
    //         'is_deleted' => $this->isDeleted,
    //         'is_forwarded' => $this->isForwarded,
    //     ]);
    // }
}
