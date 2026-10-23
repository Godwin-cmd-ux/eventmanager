<?php

namespace App\Http\Middleware;

use App\Services\Supabase\SupabaseSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupabaseGuest
{
    public function __construct(
        private readonly SupabaseSession $session,
    ) {}

    /**
     * Keep already-signed-in clerks away from the login and register screens.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->session->isLoggedIn()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
