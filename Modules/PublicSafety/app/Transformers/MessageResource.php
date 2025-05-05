<?php

namespace Modules\PublicSafety\transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'profilePic' => $this->profile_pic,
            'sender' => $this->sender,
            'messageCategoryId' => $this->message_category_id,
            'images' => $this->images,
            'message' => $this->message,
            'location' => $this->location,
            'dateSent' => $this->date_sent,
            'isDeleted' => $this->is_deleted,
            'type' => $this->type,
        ];
    }
}
