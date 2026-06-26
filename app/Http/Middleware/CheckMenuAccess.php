<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return $next($request);
        }

        $menu = Menu::where('route_name', $routeName)->first();

        if (! $menu) {
            return $next($request);
        }

        $user = $request->user();

        if ($menu->is_maintenance && $user && ! $user->isAdmin()) {
            return Inertia::render('Maintenance', [
                'message' => $menu->maintenance_message,
            ])->toResponse($request)->setStatusCode(503);
        }

        if ($user && ! in_array($user->role, $menu->role_access ?? [], true)) {
            abort(403);
        }

        return $next($request);
    }
}
