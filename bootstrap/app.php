<?php

if (is_file($__ensure = __DIR__.'/../scripts/ensure-storage-dirs.php')) {
    require $__ensure;
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ForceJsonRequestHeader;
use Illuminate\Auth\AuthenticationException;
use App\Http\Middleware\Cors;
use Modules\UBForms\Http\Middleware\CheckUBFormsAccess;
use Modules\Auth\Http\Middleware\EnsureUserHasRole;
use Modules\Auth\Http\Middleware\EnsureUserHasPermission;
use Illuminate\Http\Request;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // This API has no web login route; never redirect guests to route('login').
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->prepend(Cors::class);
        $middleware->api(prepend: [
            ForceJsonRequestHeader::class,
        ]);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'ubforms.user' => CheckUBFormsAccess::class,
            'has.role' => EnsureUserHasRole::class,
            'has.permission' => EnsureUserHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                ], 401);
            }
        });
    })->create();
