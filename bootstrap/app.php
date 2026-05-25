<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Fix "intended URL" redirect for subdirectory deployments.
        // The web server strips the /company-erp prefix before PHP sees the request,
        // so $request->fullUrl() returns the wrong URL. We rebuild it from APP_URL + path.
        $exceptions->renderable(function (
            \Illuminate\Auth\AuthenticationException $e,
            \Illuminate\Http\Request $request
        ) {
            if (!$request->expectsJson()) {
                $base     = rtrim(config('app.url'), '/');
                $path     = $request->getPathInfo();           // e.g. /tasks
                $qs       = $request->getQueryString();
                $intended = $base . $path . ($qs ? '?' . $qs : '');

                session(['url.intended' => $intended]);

                return redirect($e->redirectTo() ?? route('login'));
            }
        });
    })->create();
