<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
This is a custom middleware that forces the request header to be set to 
json. Default laravel behavior is to redirect the user to a login route
if the request header is not set to json. 

Author: SW

*/

class ForceJsonRequestHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Do not override Accept on multipart uploads — some clients send
        // file parts and still need the original negotiation headers intact.
        if (!$request->isMethod('GET') && str_contains((string) $request->header('Content-Type'), 'multipart/form-data')) {
            return $next($request);
        }

        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}