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
        // Di belakang reverse proxy (nginx-proxy, Cloudflare, load balancer)
        // TLS diterminasi di proxy, jadi request yang sampai ke aplikasi
        // berupa HTTP biasa. Tanpa ini Laravel membangun URL aset dengan
        // skema http:// di halaman yang dibuka lewat https://, dan browser
        // memblokirnya sebagai mixed content — CSS/JS tidak pernah termuat.
        // Aman mempercayai semua proxy karena container aplikasi tidak
        // mempublikasikan port ke internet; hanya proxy yang bisa mencapainya.
        $middleware->trustProxies(at: '*');

        $middleware->redirectUsersTo(function ($request) {
            return $request->user()?->isPlatformAdmin()
                ? route('platform.companies')
                : route('pos.terminal');
        });
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'approved' => \App\Http\Middleware\EnsureCompanyIsApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
