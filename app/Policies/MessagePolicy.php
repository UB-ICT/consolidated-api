<?php

namespace App\Policies;

use Modules\Message\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MessagePolicy
{
    /**
     * Determine whether the user can view any models.
     */

    public function view(User $user, Message $message)
    {
        return $message->chat->users->contains($user->id);
    }

    public function update(User $user, Message $message)
    {
        return $message->sender_id === $user->id;
    }

    public function delete(User $user, Message $message)
    {
        return $message->sender_id === $user->id;
    }


}
