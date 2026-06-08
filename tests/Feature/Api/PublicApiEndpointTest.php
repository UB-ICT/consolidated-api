<?php

namespace Tests\Feature\Api;

use Tests\Support\ApiRouteDiscovery;
use Tests\TestCase;

class PublicApiEndpointTest extends TestCase
{
    /**
     * Routes that are public at middleware level but enforce their own auth in the controller.
     *
     * @var list<string>
     */
    private const CONTROLLER_AUTH_URIS = [
        'api/user',
    ];

    public function test_every_public_endpoint_does_not_require_sanctum_authentication(): void
    {
        foreach (ApiRouteDiscovery::publicRoutes() as $route) {
            if ($route['uri'] === 'api/phpinfo' || in_array($route['uri'], self::CONTROLLER_AUTH_URIS, true)) {
                continue;
            }

            $label = $route['method'].' '.$route['uri'];
            $response = $this->callApiRoute($route);

            $this->assertNotSame(
                401,
                $response->getStatusCode(),
                'Public route should not require Sanctum auth: '.$label
            );
            $this->assertLessThan(
                600,
                $response->getStatusCode(),
                'Unexpected HTTP status for '.$label
            );
        }
    }

    public function test_google_user_endpoint_requires_bearer_token(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_phpinfo_endpoint_is_registered(): void
    {
        $phpInfoRoute = collect(ApiRouteDiscovery::publicRoutes())
            ->firstWhere('uri', 'api/phpinfo');

        $this->assertNotNull($phpInfoRoute);
        $this->assertSame('GET', $phpInfoRoute['method']);
    }
}
