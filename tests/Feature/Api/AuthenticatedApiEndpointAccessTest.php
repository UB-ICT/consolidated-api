<?php

namespace Tests\Feature\Api;

use Tests\Support\ApiRouteDiscovery;
use Tests\TestCase;

class AuthenticatedApiEndpointAccessTest extends TestCase
{
    public function test_every_sanctum_protected_endpoint_is_reachable_when_authenticated(): void
    {
        $this->actingAsSanctumUser();

        foreach (ApiRouteDiscovery::sanctumProtected() as $route) {
            $label = $route['method'].' '.$route['uri'];
            $response = $this->callApiRoute($route);

            $this->assertNotSame(
                401,
                $response->getStatusCode(),
                'Expected authenticated access for '.$label
            );
            $this->assertLessThan(
                600,
                $response->getStatusCode(),
                'Unexpected HTTP status for '.$label
            );
        }
    }
}
