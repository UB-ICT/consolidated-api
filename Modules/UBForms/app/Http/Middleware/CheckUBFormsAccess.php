<?php

namespace Modules\UBForms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckUBFormsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get the authenticated user
        $user = $request->user();

        $allowedUsers = ['james.faber@ub.edu.bz'];
        // Check if user is authenticated and in the allowed list
        if (!$user || !in_array($user->email, $allowedUsers)) {
            return response()->json(['message' => 'Unauthorized access to UBForms'], 403);
        }
        return $next($request);
    }
}
