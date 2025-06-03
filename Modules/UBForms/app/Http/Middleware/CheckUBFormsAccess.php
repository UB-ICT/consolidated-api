<?php

namespace Modules\UBForms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUBFormsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->guest(route('/Dashboard')) // or url('/login')
                ->with('error', 'You must be logged in to access this module.');
        }

        if ($this->isUBFormsRoute($request)) {
            if (Auth::user()->username !== 'jfaber') {
                Auth::logout();
                return redirect()->route('login')
                    ->with('error', 'You are not authorized to access UBForms module.');
            }
        }

        return $next($request);
    }

    protected function isUBFormsRoute(Request $request): bool
    {
        return str_contains(strtolower($request->path()), 'v1/UBForms');
    }
}
