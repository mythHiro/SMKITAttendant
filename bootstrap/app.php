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
    ->withMiddleware(function (Middleware $middleware) {
        // 1. Matikan CSRF khusus untuk endpoint API / IoT
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // 2. Daftarkan alias middleware kamu
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'device' => \App\Http\Middleware\VerifyDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
