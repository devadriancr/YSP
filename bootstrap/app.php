<?php

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
        // Only a proxy on this same server (e.g. IIS ARR) may set X-Forwarded-*; trusting any (*)
        // would let a client fake its IP and skip the login limit and SYNC_ALLOWED_IPS
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->redirectGuestsTo('/signin');
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
        // The work calendar starts with spaces (working days): sync data is kept as sent
        $middleware->trimStrings(except: [
            fn (Request $request) => $request->is('api/sync/*'),
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
