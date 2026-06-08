<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ApiRouteDiscovery
{
    /**
     * @return list<array{method: string, uri: string, middleware: list<string>, action: string, requires_sanctum: bool}>
     */
    public static function all(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! Str::startsWith($uri, 'api/')) {
                continue;
            }

            $methods = array_values(array_filter(
                $route->methods(),
                static fn (string $method): bool => ! in_array($method, ['HEAD', 'OPTIONS'], true)
            ));

            if ($methods === []) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $requiresSanctum = self::requiresSanctum($middleware);

            foreach ($methods as $method) {
                $key = strtoupper($method).' '.$uri;

                $routes[$key] = [
                    'method' => strtoupper($method),
                    'uri' => $uri,
                    'middleware' => $middleware,
                    'action' => $route->getActionName(),
                    'requires_sanctum' => $requiresSanctum,
                ];
            }
        }

        ksort($routes);

        return array_values($routes);
    }

    /**
     * @return list<array{method: string, uri: string, middleware: list<string>, action: string, requires_sanctum: bool}>
     */
    public static function sanctumProtected(): array
    {
        return array_values(array_filter(
            self::all(),
            static fn (array $route): bool => $route['requires_sanctum']
        ));
    }

    /**
     * @return list<array{method: string, uri: string, middleware: list<string>, action: string, requires_sanctum: bool}>
     */
    public static function publicRoutes(): array
    {
        return array_values(array_filter(
            self::all(),
            static fn (array $route): bool => ! $route['requires_sanctum']
        ));
    }

    /**
     * @param  list<string>  $middleware
     */
    public static function requiresSanctum(array $middleware): bool
    {
        foreach ($middleware as $entry) {
            if ($entry === 'auth:sanctum') {
                return true;
            }

            if (str_contains($entry, 'Authenticate:sanctum')) {
                return true;
            }
        }

        return false;
    }
}
