<?php

namespace Tests\Concerns;

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Tests\Support\ApiRouteUriResolver;

trait InteractsWithApi
{
    protected function configureTestingDatabaseConnections(): void
    {
        config([
            'database.connections.pgsql' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.connections.porsql' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
    }

    protected function actingAsSanctumUser(?User $user = null): User
    {
        $user ??= $this->makeSanctumUser();

        Sanctum::actingAs($user);

        return $user;
    }

    protected function makeSanctumUser(): User
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test.user@ub.edu.bz',
            'status' => 'active',
        ]);
        $user->id = (string) Str::uuid();
        $user->exists = true;

        return $user;
    }

    /**
     * @param  array{method: string, uri: string}  $route
     */
    protected function callApiRoute(array $route, array $data = []): TestResponse
    {
        $uri = '/'.ApiRouteUriResolver::resolve($route['uri']);
        $method = strtolower($route['method']);

        if (in_array($method, ['post', 'put', 'patch'], true)) {
            return $this->json($method, $uri, $data);
        }

        return $this->json($method, $uri);
    }
}
