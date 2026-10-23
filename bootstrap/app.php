<?php

use App\Http\Middleware\EnsureClerkActive;
use App\Http\Middleware\EnsureSupabaseGuest;
use App\Http\Middleware\EnsureSupabaseUser;
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
        // Behind a TLS-terminating proxy (load balancer, CDN, nginx sidecar)
        // the forwarded headers have to be trusted, otherwise Laravel builds
        // http:// URLs and drops secure session cookies. Opt in from the
        // environment: TRUSTED_PROXIES="*" or a comma-separated CIDR list.
        $proxies = env('TRUSTED_PROXIES');

        if (is_string($proxies) && $proxies !== '') {
            $middleware->trustProxies(
                at: $proxies === '*'
                    ? '*'
                    : array_map('trim', explode(',', $proxies)),
            );
        }

        $middleware->alias([
            'supabase.user' => EnsureSupabaseUser::class,
            'guest.session' => EnsureSupabaseGuest::class,
            'clerk.active' => EnsureClerkActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
