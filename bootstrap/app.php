<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'not_banned' => \App\Http\Middleware\EnsureNotBanned::class,
            'check.menu' => \App\Http\Middleware\CheckMenuAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return redirect()->route('sso.redirect');
        });

        $exceptions->respond(function (Response $response) {
            if (in_array($response->getStatusCode(), [400, 403, 404, 500, 502, 503])
                && ! app()->runningInConsole()
            ) {
                return Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse(request())
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
