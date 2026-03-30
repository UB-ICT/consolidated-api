<?php

namespace Modules\UBForms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUBFormsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get the authenticated user
        return $next($request);
    }
}
