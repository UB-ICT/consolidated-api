<?php

namespace Modules\UBForms\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Modules\UBForms\Http\Middleware\CheckUBFormsAccess;
use Tests\TestCase;

class CheckUBFormsAccessTest extends TestCase
{
    public function test_middleware_allows_request_to_continue(): void
    {
        $middleware = new CheckUBFormsAccess;
        $request = Request::create('/api/v1/UBForms/menu', 'GET');

        $response = $middleware->handle(
            $request,
            static fn (Request $handledRequest) => response()->json(['ok' => true], 200)
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['ok' => true], json_decode($response->getContent(), true));
    }
}
