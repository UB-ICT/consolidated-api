<?php

namespace Tests\Feature\Api;

use Tests\Support\ApiRouteDiscovery;
use Tests\TestCase;

class ApiRouteRegistrationTest extends TestCase
{
    public function test_all_api_routes_are_registered(): void
    {
        $routes = ApiRouteDiscovery::all();

        $this->assertGreaterThanOrEqual(270, count($routes));
    }

    public function test_every_api_route_points_to_a_resolvable_action(): void
    {
        foreach (ApiRouteDiscovery::all() as $route) {
            $label = $route['method'].' '.$route['uri'];

            $this->assertStringStartsWith('api/', $route['uri'], $label);
            $this->assertNotSame('', $route['action'], $label);

            if ($route['action'] !== 'Closure') {
                $this->assertStringContainsString('@', $route['action'], $label);
            }
        }
    }
}
