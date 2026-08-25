<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_get_vapid_public_key(): void
    {
        $response = $this->getJson('/api/v1/push/vapid-public-key');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_store_subscription(): void
    {
        $response = $this->postJson('/api/v1/push/subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/fake',
            'keys'     => ['p256dh' => 'abc', 'auth' => 'xyz'],
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_delete_subscription(): void
    {
        $response = $this->deleteJson('/api/v1/push/subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/fake',
        ]);

        $response->assertStatus(401);
    }

    public function test_vapid_public_key_returns_503_when_not_configured(): void
    {
        $this->actingAsSanctumUser();

        config(['webpush.vapid.public_key' => null]);

        $response = $this->getJson('/api/v1/push/vapid-public-key');

        $response->assertStatus(503);
    }

    public function test_vapid_public_key_returns_key_when_configured(): void
    {
        $this->actingAsSanctumUser();

        config(['webpush.vapid.public_key' => 'BTEST_PUBLIC_KEY_BASE64URL']);

        $response = $this->getJson('/api/v1/push/vapid-public-key');

        $response->assertStatus(200)
                 ->assertJson(['success' => true, 'public_key' => 'BTEST_PUBLIC_KEY_BASE64URL']);
    }

    public function test_store_subscription_validates_required_fields(): void
    {
        $this->actingAsSanctumUser();

        $response = $this->postJson('/api/v1/push/subscriptions', []);

        $response->assertStatus(422);
    }

    public function test_store_subscription_validates_endpoint_is_url(): void
    {
        $this->actingAsSanctumUser();

        $response = $this->postJson('/api/v1/push/subscriptions', [
            'endpoint' => 'not-a-url',
            'keys'     => ['p256dh' => 'abc', 'auth' => 'xyz'],
        ]);

        $response->assertStatus(422);
    }

    public function test_delete_subscription_returns_404_when_not_found(): void
    {
        $this->actingAsSanctumUser();

        $response = $this->deleteJson('/api/v1/push/subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/nonexistent',
        ]);

        $response->assertStatus(404);
    }
}
