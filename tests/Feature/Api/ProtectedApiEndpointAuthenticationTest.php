<?php

namespace Tests\Feature\Api;

use Tests\Support\ApiRouteDiscovery;
use Tests\TestCase;

class ProtectedApiEndpointAuthenticationTest extends TestCase
{
    public function test_every_sanctum_protected_endpoint_requires_authentication(): void
    {
        $routes = ApiRouteDiscovery::sanctumProtected();

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $label = $route['method'].' '.$route['uri'];

            $response = $this->callApiRoute($route);

            $this->assertSame(
                401,
                $response->getStatusCode(),
                'Expected 401 Unauthorized for '.$label
            );
        }
    }
}
