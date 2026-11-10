<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Serve Vite assets missing from the web root (stale public_html/build on Hostinger).
            // Registered outside the "web" group: no session, cookies or CSRF for static files.
            Route::get('/build/{path}', fn (string $path) => \App\Support\BuildAsset::response($path))
                ->where('path', '.*')
                ->name('build.asset');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\LogoutBlockedUsers::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'super_admin' => \App\Http\Middleware\IsSuperAdmin::class,
            'mobile.verified' => \App\Http\Middleware\EnsureMobileIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
