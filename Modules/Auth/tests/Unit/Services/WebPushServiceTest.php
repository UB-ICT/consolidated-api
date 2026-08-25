<?php

namespace Modules\Auth\Tests\Unit\Services;

use Modules\Auth\Services\WebPushService;
use PHPUnit\Framework\TestCase;

class WebPushServiceTest extends TestCase
{
    public function test_webpush_service_exists(): void
    {
        $this->assertTrue(class_exists(WebPushService::class));
    }

    public function test_send_to_user_method_exists(): void
    {
        $this->assertTrue(method_exists(WebPushService::class, 'sendToUser'));
    }

    public function test_send_method_exists(): void
    {
        $this->assertTrue(method_exists(WebPushService::class, 'send'));
    }

    public function test_send_does_nothing_when_vapid_keys_not_configured(): void
    {
        // With no VAPID keys configured the send() call should be a no-op.
        // We verify this by confirming no exception is thrown.
        $service      = new WebPushService();
        $subscription = new \Modules\Auth\Models\PushSubscription();
        $subscription->endpoint = 'https://fcm.googleapis.com/fcm/send/fake';

        $this->expectNotToPerformAssertions();

        $service->send($subscription, ['title' => 'Test', 'body' => 'Hello']);
    }
}
