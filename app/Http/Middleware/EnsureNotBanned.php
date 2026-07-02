<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_banned) {
            if ($request->routeIs('banned') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('banned');
        }

        return $next($request);
    }
}
