<?php

namespace Modules\Message\Policies;

use App\Models\User;
use Modules\Message\Models\Chat;

class ChatPolicy
{
    public function view(User $user, Chat $chat)
    {
        return $chat->users->contains($user->id);
    }

    public function update(User $user, Chat $chat)
    {
        // Only group chat admins or the chat creator can update
        return $chat->is_group && 
               ($user->id === $chat->created_by || 
                $chat->admins->contains($user->id));
    }

    public function delete(User $user, Chat $chat)
    {
        // Only group chat admins or the chat creator can delete
        return $chat->is_group && 
               ($user->id === $chat->created_by || 
                $chat->admins->contains($user->id));
    }
}