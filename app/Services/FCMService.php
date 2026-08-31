<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Throwable;

final class FCMService
{
    private static ?Messaging $messaging = null;

    public static function messaging(): ?Messaging
    {
        if (self::$messaging !== null) {
            return self::$messaging;
        }

        $credentialsPath = config('firebase.credentials_path');

        if (!is_string($credentialsPath) || !is_file($credentialsPath)) {
            return null;
        }

        try {
            $factory = (new Factory())->withServiceAccount($credentialsPath);
            self::$messaging = $factory->createMessaging();
        } catch (Throwable $exception) {
            Log::warning('FCM messaging client unavailable: '.$exception->getMessage());

            return null;
        }

        return self::$messaging;
    }

    /**
     * @param  array{title: string, body: string, data?: array<string, scalar|null>}  $payload
     */
    public static function sendToToken(string $token, array $payload): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        $messaging = self::messaging();

        if (!$messaging) {
            return false;
        }

        $data = [];

        foreach ($payload['data'] ?? [] as $key => $value) {
            if ($value === null) {
                continue;
            }

            $data[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        try {
            $message = CloudMessage::new()
                ->toToken($token)
                ->withNotification(FcmNotification::create(
                    $payload['title'],
                    $payload['body']
                ))
                ->withData($data)
                ->withWebPushConfig(WebPushConfig::fromArray([
                    'notification' => [
                        'title' => $payload['title'],
                        'body' => $payload['body'],
                        'icon' => config('firebase.web_push_icon', '/vite.svg'),
                        'silent' => false,
                    ],
                    'fcm_options' => [
                        'link' => $data['url'] ?? config('app.url'),
                    ],
                ]));

            $messaging->send($message);

            return true;
        } catch (MessagingException $exception) {
            Log::warning('FCM send failed', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        } catch (Throwable $exception) {
            Log::error('FCM unexpected error: '.$exception->getMessage());

            return false;
        }
    }
}
