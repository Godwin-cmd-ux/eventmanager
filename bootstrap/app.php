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
        $middleware->alias([
            'supabase.user' => EnsureSupabaseUser::class,
            'guest.session' => EnsureSupabaseGuest::class,
            'clerk.active' => EnsureClerkActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
