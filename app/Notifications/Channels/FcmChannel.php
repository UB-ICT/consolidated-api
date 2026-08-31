<?php

namespace App\Notifications\Channels;

use App\Services\FCMService;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;

final class FcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!$notifiable instanceof User) {
            return;
        }

        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $token = $notifiable->device_token;

        if (!is_string($token) || trim($token) === '') {
            return;
        }

        /** @var array{title: string, body: string, data?: array<string, scalar|null>} $payload */
        $payload = $notification->toFcm($notifiable);

        FCMService::sendToToken($token, $payload);
    }
}
