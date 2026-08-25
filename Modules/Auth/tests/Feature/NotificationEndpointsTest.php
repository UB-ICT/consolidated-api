<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_list_notifications(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_get_unread_count(): void
    {
        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_mark_all_as_read(): void
    {
        $response = $this->postJson('/api/v1/notifications/mark-all-read');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_mark_notification_as_read(): void
    {
        $response = $this->postJson('/api/v1/notifications/fake-id/mark-read');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_delete_notification(): void
    {
        $response = $this->deleteJson('/api/v1/notifications/fake-id');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_notifications(): void
    {
        $this->actingAsSanctumUser();

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'unread_count', 'data']);
    }

    public function test_authenticated_user_can_get_unread_count(): void
    {
        $this->actingAsSanctumUser();

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'count']);
    }

    public function test_authenticated_user_mark_all_read_returns_success(): void
    {
        $this->actingAsSanctumUser();

        $response = $this->postJson('/api/v1/notifications/mark-all-read');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_mark_read_returns_404_for_unknown_notification(): void
    {
        $this->actingAsSanctumUser();

        $response = $this->postJson('/api/v1/notifications/' . \Illuminate\Support\Str::uuid() . '/mark-read');

        $response->assertStatus(404);
    }

    public function test_delete_returns_404_for_unknown_notification(): void
    {
        $this->actingAsSanctumUser();

        $response = $this->deleteJson('/api/v1/notifications/' . \Illuminate\Support\Str::uuid());

        $response->assertStatus(404);
    }
}
