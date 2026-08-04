<?php

use App\Http\Middleware\EnsureRequestOriginatesFromLagos;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (like Heroku/Railway) sits in front of the app as the only
        // way in - it's not an untrusted network hop, so trusting it here
        // is safe. Without this, $request->ip() returns Render's internal
        // routing IP instead of the real visitor IP, which breaks both the
        // IP-based Nigeria check and per-visitor rate limiting.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'lagos.only' => EnsureRequestOriginatesFromLagos::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
