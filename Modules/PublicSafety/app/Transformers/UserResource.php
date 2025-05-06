<?php

namespace Modules\PublicSafety\transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'domain' => $this->domain,
            'password' => $this->password,
            'roleId' => $this->role_id,
            'campusId' => $this->campus_id,
            'userStatusId' => $this->user_status_id,
            'profilePicture' => $this->profile_picture,
        ];
    }
}